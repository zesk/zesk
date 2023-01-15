<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage server
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Application;
use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Timeout;
use zesk\Exception_Unimplemented;
use zesk\Exception_Unsupported;
use zesk\Hooks;
use zesk\Temporal;
use zesk\Timer;
use zesk\Timestamp;

/**
 * Support locks across processes, servers.
 * Specific to the database, however.
 *
 * @author kent
 * @see Class_Lock
 * @property integer $id
 * @property string $code
 * @property integer $pid
 * @property Server $server
 * @property timestamp $locked
 * @property timestamp $used
 */
class Lock extends ORMBase {
	/**
	 *
	 * @var array
	 */
	private static array $locks = [];

	/**
	 * Register all zesk hooks.
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(Server::class . '::delete', self::server_delete(...));
		$application->hooks->add(Hooks::HOOK_RESET, self::releaseAll(...));
		$application->hooks->add(Hooks::HOOK_EXIT, self::releaseAll(...), ['first' => true]);
	}

	/**
	 * Retrieve the cached version of a lock or register one
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Lock
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 * @throws Database_Exception_SQL
	 */
	public static function instance(Application $application, string $code): self {
		/* @var $lock Lock */
		$lock = self::$locks[strtolower($code)] ?? null;
		if (!$lock) {
			$lock = self::_create_lock($application, $code);
		} elseif (!$lock->_isMine()) {
			try {
				$lock->fetch();
			} catch (Exception_ORMNotFound) {
				// Has since been deleted
				$lock = self::_create_lock($application, $code);
			}
		}
		return $lock;
	}

	/**
	 * Once per hour, cull locks which are old
	 */
	public static function cron_cluster_hour(Application $application): void {
		try {
			self::deleteUnused($application);
		} catch (Database_Exception_Table_NotFound $e) {
			$application->logger->error($e);
		}
	}

	/**
	 * Once a minute, release locks associated with processes which are dead,
	 * or are associated with a dead server (no longer exists)
	 */
	public static function cron_cluster_minute(Application $application): void {
		self::deleteDeadProcesses($application);
		self::deleteDangling($application);
	}

	/**
	 * Delete Locks which have not been used in the past 24 hours
	 * @throws Database_Exception_Table_NotFound
	 */
	public static function deleteUnused(Application $application): int {
		$query = $application->ormRegistry(__CLASS__)->queryDelete();

		try {
			$n_rows = $query->appendWhere([
				'used|<=' => Timestamp::now()->addUnit(-1, Temporal::UNIT_DAY), 'server' => null, 'pid' => null,
			])->execute()->affectedRows();
			if ($n_rows > 0) {
				$application->logger->notice('Deleted {n_rows} {locks} which were unused in the past 24 hours.', [
					'n_rows' => $n_rows, 'locks' => $application->locale->plural(__CLASS__, $n_rows),
				]);
			}
			return $n_rows;
		} catch (Exception_Semantics|Exception_Key|Database_Exception|Database_Exception_SQL|Database_Exception_Duplicate $e) {
			throw new Database_Exception_Table_NotFound($query->database(), strval($query), $e->getMessage(), $e->variables(), $e->getCode(), $e);
		}
	}

	/**
	 * @param Lock $lock
	 * @param string $context
	 * @return void
	 */
	private static function releaseLock(Lock $lock, string $context = ''): void {
		try {
			$server_id = $lock->memberInteger('server');
		} catch (Exception_Key $e) {
			$server_id = $e::class;
		}
		$lock->release();
		$lock->application->logger->notice('Releasing lock #{id} {code} associated with defunct server # {server_id} (current server ids: {context})', $lock->variables() + [
			'server_id' => $server_id, 'context' => $context,
		]);
	}

	/**
	 * Delete locks whose server doesn't link to a valid row in the Server table
	 */
	public static function deleteDangling(Application $application): int {
		// Deleting unlinked locks
		$rowCount = 0;
		$serverIDs = $application->ormRegistry(Server::class)->querySelect()->toArray(null, 'id');
		if (count($serverIDs) === 0) {
			return 0;
		}
		$iterator = $application->ormRegistry(__CLASS__)->querySelect()->addWhere('X.server|!=|AND', $serverIDs)->ormIterator();
		foreach ($iterator as $lock) {
			self::releaseLock($lock, implode(',', $serverIDs));
			++$rowCount;
		}
		return $rowCount;
	}

	/**
	 * Delete Locks associated with this server which do not have a valid PID
	 */
	public static function deleteDeadProcesses(Application $application): void {
		$timeout_seconds = -abs($application->configuration->getPath([__CLASS__, 'timeout_seconds'], 100));

		try {
			$you_are_dead_to_me = Timestamp::now()->addUnit($timeout_seconds);
			$iterator = $application->ormRegistry(__CLASS__)->querySelect()->appendWhere([
				'server' => Server::singleton($application), 'locked|<=' => $you_are_dead_to_me,
			])->ormIterator();
			/* @var $lock Lock */
			foreach ($iterator as $lock) {
				(function (Lock $lock): void {
					if (!$lock->isProcessAlive()) {
						// Delete this way so hooks get called per dead server
						$lock->application->logger->warning('Releasing lock {code} (#{id}), associated with dead process, locked on {locked}', $lock->members());
						$lock->release();
					}
				})($lock);
			}
		} catch (Exception_Key|Exception_Semantics $e) {
		}
	}

	/**
	 * Acquire exclusive access to a lock, optionally waiting for availability.
	 *
	 * <code>
	 * $lock = Lock::instance("foo")->acquire(); // Returns null immediately if can't get lock
	 * $lock = Lock::instance("foo")->acquire(null); // Returns null immediately if can't get lock
	 * $lock = Lock::instance("foo")->acquire(0); // Waits forever
	 * $lock = Lock::instance("foo")->acquire(1); // Tries to get lock for one second, then throws
	 * Exception_Timeout
	 * </code>
	 *
	 * @param int $timeout
	 *            Time, in seconds, to wait until
	 * @return Lock
	 * @throws Exception_Timeout
	 */
	public function acquire(int $timeout = 0): Lock {
		if ($this->_isMine()) {
			return $this;
		}
		if ($this->_isMyServer() && !$this->isProcessAlive()) {
			return $this->_acquireDead();
		}
		if ($timeout === 0) {
			$this->_isLocked();
			return $this->_acquireOnce();
		}
		if ($timeout < 0) {
			throw new Exception_Timeout('Acquire timeout is negative {timeout}', ['timeout' => $timeout]);
		}
		return $this->_acquire($timeout);
	}

	/**
	 * Acquire a lock or throw an Exception_Lock
	 *
	 * @param int|null $timeout
	 * @return Lock
	 * @return $this
	 * @throws Exception_Timeout
	 */
	public function expect(int $timeout = null): self {
		return $this->acquire($timeout);
	}

	/**
	 * Release all locks from my server/process
	 */
	public static function releaseAll(Application $application): void {
		self::$locks = [];
		$application->logger->debug(__METHOD__);

		try {
			$server = Server::singleton($application);
			$query = $application->ormRegistry(__CLASS__)->queryUpdate();
			$query->setValues([
				'pid' => null, 'server' => null, 'locked' => null,
			])->appendWhere([
				'pid' => $application->process->id(), 'server' => $server,
			])->execute();
		} catch (Exception_Unsupported|Exception_ORMNotFound|Database_Exception_Table_NotFound $e) {
			// Ignore for now - likely database misconfigured
		}
	}

	/**
	 * Hook called when server is deleted.
	 * Deletes related locks.
	 *
	 * @param Server $server
	 */
	public static function server_delete(Server $server): void {
		$application = $server->application;
		$query = $application->ormRegistry(__CLASS__)->queryDelete()->addWhere('server', $server);
		$query->execute();
		if (($n_rows = $query->affectedRows()) > 0) {
			$application->logger->warning('Deleted {n} {locks} associated with server {name} (#{id})', [
				'n' => $n_rows, 'locks' => $application->locale->plural(__CLASS__, $n_rows),
			] + $server->members());
		}
	}

	/**
	 * Break a lock.
	 * PHP5 does not allow functions called "break", PHP7 does.
	 *
	 * @return self
	 */
	public function crack(): self {
		$this->pid = $this->server = null;
		return $this->store();
	}

	/**
	 * Locked by SOMEONE ELSE
	 */
	public function is_locked() {
		if ($this->memberIsEmpty('pid') && $this->memberIsEmpty('server')) {
			return false;
		}
		return $this->_isLocked();
	}

	/**
	 * Release a lock I have
	 *
	 * @return Lock
	 */
	public function release() {
		$this->queryUpdate()->setValues([
			'pid' => null, 'server' => null, 'locked' => null,
		])->appendWhere([
			'id' => $this->id,
		])->execute();
		$this->application->logger->debug("Released lock $this->code");
		$this->pid = null;
		$this->server = null;
		return $this;
	}

	/**
	 * Register or create a lock
	 *
	 * @param string $code
	 */
	private static function _create_lock(Application $application, $code) {
		$lock = $application->ormFactory(__CLASS__, [
			'code' => $code,
		]);

		try {
			if (!$lock->find()) {
				$lock->store();
			}
		} catch (Exception_ORMDuplicate $dup) {
			$lock->find();
		}
		return self::$locks[strtolower($code)] = $lock;
	}

	/**
	 * Is this Lock locked by SOMEONE BESIDES MY PROCESS?
	 *
	 * Logic is as follows:
	 * - If it's registered to another server: We run a hook to check. If no hook, then we assume
	 * it's locked.
	 * - Now we assume the lock is on my current server.
	 * - If it's my process ID, then it's not locked by someone else, return false.
	 * - If the other process is still alive, it's locked, return true.
	 * - The other process is dead, so we release the lock. It's no longer alive, return false.
	 *
	 * @return boolean
	 */
	private function _isLocked() {
		// Each server is responsible for keeping locks clean.
		// Allow a hook to enable inter-server connection, later
		if (!$this->_isMyServer()) {
			return $this->application->hooks->callArguments(__CLASS__ . '::server_is_locked', [
				$this->memberInteger('server'), $this->pid,
			], true);
		}
		if ($this->_is_my_pid()) {
			// My process, so it's not locked
			return false;
		}
		// Is the process running?
		if ($this->application->process->alive($this->pid)) {
			return true;
		}
		$this->application->logger->warning('Releasing lock from {server}:{pid} as process is dead', $this->members());
		$this->release();
		return false;
	}

	/**
	 * Acquire a lock with an optional where clause
	 *
	 * @param array $where
	 */
	private function _acquire_where(array $where = []) {
		$update = $this->queryUpdate();
		$sql = $update->sql();
		$update->setValues([
			'pid' => $this->application->process->id(), 'server' => Server::singleton($this->application),
			'*locked' => $sql->now(), '*used' => $sql->now(),
		])->appendWhere([
			'id' => $this->id,
		] + $where)->execute();
		if ($this->fetch()->_isMine()) {
			$this->application->logger->debug("Acquired lock $this->code");
			return $this;
		}
		return null;
	}

	/**
	 * Acquire an inactive lock
	 */
	private function _acquireOnce() {
		return $this->_acquire_where([
			'pid' => null,
		]);
	}

	/**
	 * Acquire a dead lock, requires that the pid and server don't change between now and
	 * acquisition
	 */
	private function _acquireDead() {
		return $this->_acquire_where([
			'pid' => $this->pid, 'server' => $this->server,
		]);
	}

	/**
	 * Loop and try to get lock
	 *
	 * @param int $timeout
	 * @throws Exception_Timeout
	 */
	private function _acquire($timeout) {
		$timer = new Timer();
		// Defaults to 0.5 seconds
		$sleep = $this->optionFloat('sleep_seconds', 0.5);
		if ($timeout > 0 && $timeout < $sleep) {
			$sleep = $timeout;
		}
		$sleep = intval($sleep * 1000000);
		$delete_attempt = false;
		while (true) {
			if (!$delete_attempt) {
				self::deleteDeadProcesses($this->application);
				$delete_attempt = true;
			} else {
				usleep($sleep);
			}
			if ($this->_is_free()) {
				if ($this->_acquireOnce()) {
					return $this;
				}
			} else {
				if (!$this->fetch()->_isLocked()) {
					continue;
				}
			}
			if ($timeout > 0 && $timer->elapsed() > $timeout) {
				throw new Exception_Timeout('Waiting for Lock "{code}" ({timeout} seconds)', [
					'method' => __METHOD__, 'timeout' => $timeout, 'code' => $this->code,
				]);
			}
		}
	}

	/**
	 * Checks if the PID in this Lock is alive
	 *
	 * @return boolean
	 */
	private function isProcessAlive() {
		return $this->application->process->alive($this->pid);
	}

	/**
	 * Is this my server?
	 *
	 * @return boolean
	 */
	private function _isMyServer() {
		return Server::singleton($this->application)->id === $this->memberInteger('server');
	}

	/**
	 * Does my PID match (DOES NOT MEAN SERVER WILL MATCH)
	 *
	 * @return boolean
	 */
	private function _is_my_pid() {
		return $this->application->process->id() === $this->memberInteger('pid');
	}

	/**
	 * Is this lock free?
	 *
	 * @return boolean
	 */
	private function _is_free() {
		return $this->memberIsEmpty('pid') && $this->memberIsEmpty('server');
	}

	/**
	 * Implies PID and server match
	 *
	 * @return boolean
	 */
	private function _isMine() {
		return $this->_isMyServer() && $this->_is_my_pid();
	}
}
