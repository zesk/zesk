<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage server
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Throwable;
use zesk\Application;
use zesk\Application\Hooks;
use zesk\Cron\Attributes\Cron;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\CodeName;
use zesk\Exception;
use zesk\Exception\LockedException;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;
use zesk\HookMethod;
use zesk\Kernel;
use zesk\PHP;
use zesk\Temporal;
use zesk\Timer;
use zesk\Timestamp;

/**
 * Support locks across processes, servers.
 * Specific to the database, however.
 */
#[Entity]
class Lock extends Model
{
	use AutoID;
	use CodeName;

	#[Column(type: 'integer', nullable: true)]
	protected null|int $pid = null;

	#[ManyToOne]
	#[JoinColumn(name: 'server', nullable: true)]
	protected null|Server $server = null;

	#[Column(type: 'timestamp', nullable: true)]
	protected null|Timestamp $locked = null;

	#[Column(type: 'timestamp', nullable: false)]
	protected Timestamp $used;

	private static int $serverLocks = 0;

	/**
	 * Retrieve the cached version of a lock or register one
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Lock
	 */
	public static function instance(Application $application, string $code): self
	{
		$em = $application->entityManager();
		$lock = $em->getRepository(Lock::class)->findOneBy(['code' => $code]);
		if (!$lock) {
			$lock = self::_create_lock($application, $code);
		} elseif (!$lock->_isMine()) {
			try {
				$em->refresh($lock);
			} catch (ORMException|TransactionRequiredException $e) {
				$lock = self::_create_lock($application, $code);
			}
		}
		return $lock;
	}

	/**
	 * Once per hour, cull locks which are old
	 * @see self::deleteUnusedLocks()
	 */
	#[Cron(schedule: Temporal::UNIT_HOUR, scope: Cron::SCOPE_SERVER)]
	public static function deleteUnusedLocks(Application $application): void
	{
		self::deleteUnused($application);
	}

	/**
	 * Once a minute, release locks associated with processes which are dead,
	 * or are associated with a dead server (no longer exists)
	 * @param Application $application
	 * @return void
	 * @see self::cullDeadThings()
	 */
	#[Cron(schedule: Temporal::UNIT_MINUTE, scope: Cron::SCOPE_SERVER)]
	public static function cullDeadThings(Application $application): void
	{
		self::deleteDeadProcesses($application);
		self::deleteDangling($application);
	}

	/**
	 * Delete Locks which have not been used in the past 24 hours
	 */
	public static function deleteUnused(Application $application): int
	{
		$em = $application->entityManager();
		$ex = Criteria::expr();
		$pastTimestamp = Timestamp::now()->addUnit(-1, Temporal::UNIT_DAY);
		$crit = Criteria::create()->where($ex->isNull('server'))->andWhere($ex->isNull('pid'))->andWhere($ex->lte('expires', $pastTimestamp));
		$n = 0;
		foreach ($em->getRepository(Lock::class)->findBy([$crit]) as $lock) {
			$em->remove($lock);
			$n++;
		}
		$em->flush();
		return $n;
	}

	/**
	 * @param Lock $lock
	 * @param string $context
	 * @return void
	 */
	private static function releaseLock(Lock $lock, string $context = ''): void
	{
		if ($context === '') {
			$context = Kernel::callingFunction();
		}
		$id = $lock->server->id;
		$lock->release();
		$lock->application->notice('Releasing lock #{id} {code} associated with defunct server # {serverId} (current server ids: {context})', [
			'id' => $lock->id, 'code' => $lock->code, 'serverId' => $id, 'context' => $context,
		]);
	}

	/**
	 * Delete locks whose server doesn't link to a valid row in the Server table
	 * @param Application $application
	 * @return int
	 * @throws SQLException
	 * @throws ORMException
	 */
	public static function deleteDangling(Application $application): int
	{
		$em = $application->entityManager();
		$query = $em->createQuery('SELECT DISTINCT server FROM ' . Lock::class);
		$serverIDs = [];
		foreach ($query->toIterable('server') as $server) {
			$serverIDs[] = $server;
		}
		// Deleting unlinked locks
		$rowCount = 0;
		if (count($serverIDs) === 0) {
			return 0;
		}
		$ex = Criteria::expr();
		$criteria = Criteria::create()->where($ex->notIn('server', $serverIDs));
		foreach ($em->getRepository(Lock::class)->findBy([$criteria]) as $lock) {
			/* @var $lock self */
			self::releaseLock($lock, implode(',', $serverIDs));
			++$rowCount;
		}
		return $rowCount;
	}

	/**
	 * Delete Locks associated with this server which do not have a valid PID
	 */
	public static function deleteDeadProcesses(Application $application): void
	{
		$timeout_seconds = -abs($application->configuration->getPath([__CLASS__, 'timeout_seconds'], 100));

		try {
			$you_are_dead_to_me = Timestamp::now()->addUnit($timeout_seconds);

			try {
				$iterator = $application->ormRegistry(__CLASS__)->querySelect()->appendWhere([
					'server' => Server::singleton($application), 'locked|<=' => $you_are_dead_to_me,
				])->ormIterator();
				foreach ($iterator as $lock) {
					/* @var $lock Lock */
					if (!$lock->isProcessAlive()) {
						// Delete this way so hooks get called per dead server
						$lock->application->warning('Releasing lock {code} (#{id}), associated with dead process, locked on {locked}', $lock->members());
						$lock->release();
					}
				}
			} catch (Throwable $e) {
				$application->error($e);
			}
			/* @var $lock Lock */
		} catch (KeyNotFound|SemanticsException) {
		}
	}

	/**
	 * Acquire exclusive access to a lock, optionally waiting for availability.
	 *
	 * <code>
	 * $lock = Lock::instance("foo")->acquire(); // Returns null immediately if can't get lock
	 * $lock = Lock::instance("foo")->acquire(-1); // throws TimeoutExpired
	 * $lock = Lock::instance("foo")->acquire(0); // Waits forever
	 * $lock = Lock::instance("foo")->acquire(1); // Tries to get lock for one second, then throws
	 * TimeoutExpired
	 * </code>
	 *
	 * @param int $timeout
	 *            Time, in seconds, to wait until
	 * @return Lock
	 * @throws TimeoutExpired
	 */
	public function acquire(int $timeout = 0): Lock
	{
		if ($this->_isMine()) {
			return $this;
		}
		if ($this->isMyServer() && !$this->isProcessAlive()) {
			return $this->_acquireDead();
		}
		if ($timeout === 0) {
			$this->_isLocked();
			return $this->_acquireOnce();
		}
		if ($timeout < 0) {
			throw new TimeoutExpired('Acquire timeout is negative {timeout}', ['timeout' => $timeout]);
		}
		$this->_acquire($timeout);
		return $this;
	}

	/**
	 * Acquire a lock or throw an LockedException
	 *
	 * @param int|null $timeout
	 * @return Lock
	 * @return $this
	 * @throws TimeoutExpired
	 */
	public function expect(int $timeout = null): self
	{
		return $this->acquire($timeout);
	}

	/**
	 * Release all locks from my server/process
	 */
	#[HookMethod(handles: [Hooks::HOOK_EXIT, Hooks::HOOK_RESET])]
	public static function releaseAll(Application $application): void
	{
		if (!$application->modules->loaded('Doctrine')) {
			return;
		}

		try {
			$em = $application->entityManager();
		} catch (Exception\NotFoundException) {
			return;
		}
		$query = $em->createQuery('UPDATE ' . Lock::class . ' SET server=NULL, pid=NULL WHERE server=:server AND pid=:pid');
		$server = Server::singleton($application);
		$result = $query->execute(['server' => $server, 'pid' => $application->process->id()]);
		$application->debug(__METHOD__ . '{method} => {result}', [
			'method' => __METHOD__, 'result' => $result,
		]);
	}

	/**
	 * Hook called when server is deleted.
	 * Deletes related locks.
	 *
	 * @param Server $server
	 */
	public static function releaseServer(Server $server): void
	{
		$application = $server->application;
		$query = $application->entityManager()->createQuery('UPDATE ' . Lock::class . ' SET server=NULL, pid=NULL WHERE server=:server');
		$server = Server::singleton($application);
		$result = $query->execute(['server' => $server]);
		if ($result > 0) {
			$application->notice(__METHOD__ . 'Deleted {result} {locks} associated with server {name} (#{id})', [
				'method' => __METHOD__, 'result' => $result, 'name' => $server->name, 'id' => $server->id,
			]);
		}
	}

	/**
	 * Break a lock.
	 * PHP5 does not allow functions called "break", PHP7 does.
	 *
	 * @return $this
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function crack(): self
	{
		$this->pid = $this->server = null;
		$this->em->persist($this);
		$this->em->flush();
		return $this;
	}

	/**
	 * Locked by SOMEONE ELSE
	 */
	public function isLocked(): bool
	{
		if (empty($this->pid) && empty($this->server)) {
			return false;
		}
		return $this->_isLocked();
	}

	/**
	 * Release a lock I have
	 *
	 * @return Lock
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function release(): self
	{
		$this->server = null;
		$this->pid = null;
		$this->em->persist($this);
		$this->em->flush();
		$this->application->debug("Released lock $this->code");
		return $this;
	}

	/**
	 * Register or create a lock
	 *
	 * @param string $code
	 */
	private static function _create_lock(Application $application, string $code): self
	{
		$em = $application->entityManager();
		$lock = $em->getRepository(Lock::class)->findBy(['code' => $code]);
		if ($lock) {
			assert($lock instanceof self);
			$lock->used = Timestamp::now();
			return $lock;
		}
		$lock = new Lock($application);
		$lock->code = $code;
		$lock->used = Timestamp::now();
		$em->persist($lock);
		return $lock;
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
	public const HOOK_IS_LOCKED = __CLASS__ . '::isLocked';

	private function _isLocked(): bool
	{
		// Each server is responsible for keeping locks clean.
		// Allow a hook to enable inter-server connection, later
		if (!$this->isMyServer()) {
			return $this->server->invokeFilters(self::HOOK_IS_LOCKED, true, [$this]);
		}
		if ($this->isMyPID()) {
			// My process, so it's not locked
			return false;
		}
		// Is the process running?
		if ($this->application->process->alive($this->pid)) {
			return true;
		}
		$this->application->warning('Releasing lock from {server}:{pid} as process is dead', [
			'server' => $this->server->id, 'pid' => $this->pid,
		]);
		$this->release();
		return false;
	}

	/**
	 * Acquire a lock with an optional where clause
	 *
	 * @param string $whereSQL
	 * @return Lock
	 * @throws LockedException
	 */
	private function _acquireWhere(string $whereSQL): self
	{
		$app = $this->application;
		$em = $this->em;
		$query = $em->createQuery('UPDATE ' . self::class . " SET pid=:pid, server=:server, locked=:now, used=:now WHERE id=:id AND $whereSQL");
		$query->setParameter('pid', $app->process->id());
		$query->setParameter('server', Server::singleton($this->application)->id);
		$query->setParameter('id', $this->id);
		$query->setParameter('now', Timestamp::nowUTC());
		if ($query->execute() !== 0) {
			try {
				$em->refresh($this);
				if ($this->_isMine()) {
					$this->application->debug("Acquired lock $this->code");
					return $this;
				}
			} catch (Throwable $t) {
				PHP::log($t);

				throw new LockedException('Failed {throwableClass} {message}', Exception::exceptionVariables($t), 0, $t);
			}
		}

		throw new LockedException("Is locked $this->>code", [], 0);
	}

	/**
	 * Acquire an inactive lock
	 */
	private function _acquireOnce(): self
	{
		return $this->_acquireWhere('pid IS NULL');
	}

	/**
	 * Acquire a dead lock, requires that the pid and server don't change between now and
	 * acquisition
	 */
	private function _acquireDead(): self
	{
		return $this->_acquireWhere('pid=:pid AND server=:server');
	}

	/**
	 * Loop and try to get lock
	 *
	 * @param int $timeout
	 * @throws TimeoutExpired
	 */
	private function _acquire(int $timeout): void
	{
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
			if ($this->isFree()) {
				if ($this->_acquireOnce()) {
					return;
				}
			} else {
				try {
					$this->em->refresh($this);
				} catch (Throwable $t) {
					PHP::log($t);
				}
				if (!$this->_isLocked()) {
					continue;
				}
			}
			if ($timeout > 0 && $timer->elapsed() > $timeout) {
				throw new TimeoutExpired('Waiting for Lock "{code}" ({timeout} seconds)', [
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
	private function isProcessAlive(): bool
	{
		return $this->application->process->alive($this->pid);
	}

	/**
	 * Is this my server?
	 *
	 * @return boolean
	 */
	private function isMyServer(): bool
	{
		try {
			return Server::singleton($this->application)->id === $this->server;
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * Does my PID match (DOES NOT MEAN SERVER WILL MATCH)
	 *
	 * @return boolean
	 */
	private function isMyPID(): bool
	{
		return $this->application->process->id() === $this->pid;
	}

	/**
	 * Is this lock free?
	 *
	 * @return boolean
	 */
	private function isFree(): bool
	{
		return empty($this->pid) && empty($this->server);
	}

	/**
	 * Implies PID and server match
	 *
	 * @return boolean
	 */
	private function _isMine(): bool
	{
		return $this->isMyServer() && $this->isMyPID();
	}
}
