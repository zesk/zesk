<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Lock.php $
 * @package zesk
 * @subpackage server
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

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
class Lock extends ORM {
	/**
	 * 
	 * @var array
	 */
	private static $locks = array();
	
	/**
	 * Register all zesk hooks.
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add(__NAMESPACE__ . '\\' . 'Server::delete', array(
			__CLASS__,
			'server_delete'
		));
		$zesk->hooks->add('exit', array(
			__CLASS__,
			"release_all"
		));
	}
	
	/**
	 * Retrieve the cached version of a lock or register one
	 *
	 * @param string $code
	 * @return Lock
	 */
	public static function instance(Application $application, $code) {
		/* @var $lock Lock */
		$lock = avalue(self::$locks, strtolower($code));
		if (!$lock) {
			$lock = self::_create_lock($application, $code);
		} else if (!$lock->_is_mine()) {
			try {
				$lock->fetch();
			} catch (Exception_ORM_NotFound $e) {
				// Has since been deleted
				$lock = self::_create_lock($application, $code);
			}
		}
		return $lock;
	}
	
	/**
	 * Once per hour, cull locks which are old
	 */
	public static function cron_cluster_hour(Application $application) {
		self::delete_unused_locks($application);
	}
	
	/**
	 * Once a minute, release locks associated with processes which are dead,
	 * or are associated with a dead server (no longer exists)
	 */
	public static function cron_cluster_minute(Application $application) {
		self::delete_dead_pids($application);
		self::delete_unlinked_locks($application);
	}
	
	/**
	 * Delete Locks which have not been used in the past 24 hours
	 */
	public static function delete_unused_locks(Application $application) {
		$n_rows = $application->query_delete(__CLASS__)
			->where(array(
			'used|<=' => Timestamp::now()->add_unit(-1, Timestamp::UNIT_DAY),
			"server" => null,
			"pid" => null
		))
			->execute()
			->affected_rows();
		if ($n_rows > 0) {
			$application->logger->notice("Deleted {n_rows} {locks} which were unused in the past 24 hours.", array(
				"n_rows" => $n_rows,
				"locks" => Locale::plural(__CLASS__, $n_rows)
			));
		}
		return $n_rows;
	}
	
	/**
	 * Delete locks whose server doesn't link to a valid row in the Server table
	 */
	public static function delete_unlinked_locks(Application $application) {
		// Deleting unlinked locks
		$n_rows = 0;
		$server_ids = $application->orm_registry(Server::class)->query_select()->to_array(null, "id");
		$iterator = $application->orm_registry(__CLASS__)
			->query_select()
			->where("X.server|!=|AND", $server_ids)
			->object_iterator();
		foreach ($iterator as $lock) {
			/* @var $lock Lock */
			$server_id = $lock->member_integer("server");
			$lock->release();
			$application->logger->notice("Releasing lock #{id} {code} associated with defunct server # {server_id} (current server ids: {server_ids})", $lock->variables() + array(
				"server_id" => $server_id,
				"server_ids" => implode(",", $server_ids)
			));
			++$n_rows;
		}
		return $n_rows;
	}
	
	/**
	 * Delete Locks associated with this server which do not have a valid PID
	 */
	public static function delete_dead_pids(Application $application) {
		$timeout_seconds = -abs($application->configuration->path_get(__CLASS__ . "::timeout_seconds", 100));
		$you_are_dead_to_me = Timestamp::now()->add_unit($timeout_seconds, Timestamp::UNIT_SECOND);
		$iterator = $application->orm_registry(__CLASS__)
			->query_select()
			->where(array(
			'server' => Server::singleton($application),
			'locked|<=' => $you_are_dead_to_me
		))
			->object_iterator();
		/* @var $lock Lock */
		foreach ($iterator as $lock) {
			if (!$lock->is_process_alive()) {
				// Delete this way so hooks get called per dead server
				$application->logger->warning("Releasing lock {code} (#{id}), associated with dead process, locked on {locked}", $lock->members());
				$lock->release();
			}
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
	 * @param double $timeout
	 *        	Time, in seconds, to wait until
	 * @return Lock|null
	 */
	public function acquire($timeout = null) {
		if ($this->_is_mine()) {
			return $this;
		}
		if ($this->_is_my_server() && !$this->is_process_alive()) {
			return $this->_acquire_dead();
		}
		if ($timeout === null) {
			$this->_is_locked();
			return $this->_acquire_once();
		}
		if (!is_integer($timeout) || $timeout < 0) {
			return null;
		}
		return $this->_acquire(intval($timeout));
	}
	
	/**
	 * Acquire a lock or throw an Exception_Lock
	 *
	 * @param string $code
	 * @param integer $timeout
	 *        	Optional timeout
	 * @throws Exception_Lock
	 * @return Lock
	 */
	public function expect($timeout = null) {
		$result = $this->acquire($timeout);
		if ($result) {
			return $result;
		}
		throw new Exception_Lock("Unable to obtain lock {code} (timeout={timeout}", $this->members("code") + array(
			"timeout" => $timeout
		));
	}
	/**
	 * Get a lock or throw an Exception_Lock
	 *
	 * @param string $code
	 * @param integer $timeout
	 *        	Optional timeout
	 * @throws Exception_Lock
	 * @return Lock
	 * @deprecated 2017-08 use require() 
	 * @see
	 */
	public static function require_lock($code, $timeout = null) {
		zesk()->deprecated();
		$lock = self::get_lock($code, $timeout);
		if (!$lock) {
			throw new Exception_Lock("Unable to obtain lock {code} (timeout={timeout}", compact("code", "timeout"));
		}
		return $lock;
	}
	/**
	 * Fetch a lock by code, optionally waiting for availability.
	 *
	 * <code>
	 * $lock = Lock::get_lock("foo"); // Returns null immediately if can't get lock
	 * $lock = Lock::get_lock("foo", null); // Returns null immediately if can't get lock
	 * $lock = Lock::get_lock("foo", 0); // Waits forever
	 * $lock = Lock::get_lock("foo", 1); // Tries to get lock for one second, then throws
	 * Exception_Timeout
	 * </code>
	 *
	 * @param string $code
	 * @param double $timeout
	 *        	Time, in seconds, to wait until
	 * @return Lock|null
	 * @deprecated 2017-08 see self::acquire
	 */
	public static function get_lock($code, $timeout = null) {
		zesk()->deprecated();
		$lock = self::instance(app(), $code);
		if ($lock->_is_mine()) {
			return $lock;
		}
		if ($lock->_is_my_server() && !$lock->is_process_alive()) {
			return $lock->_acquire_dead();
		}
		if ($timeout === null) {
			$lock->_is_locked();
			return $lock->_acquire_once();
		}
		if (!is_integer($timeout) || $timeout < 0) {
			return null;
		}
		return $lock->_acquire(intval($timeout));
	}
	
	/**
	 * Release all locks from my server/process
	 */
	public static function release_all(Application $application) {
		/* @var $zesk Kernel */
		self::$locks = array();
		try {
			$application->query_update(__CLASS__)->values(array(
				'pid' => null,
				'server' => null,
				'locked' => null
			))->where(array(
				'pid' => zesk()->process_id(),
				'server' => Server::singleton($application)
			));
		} catch (Exception $e) {
			// Ignore for now - likely database misconfigured
		}
	}
	
	/**
	 * Hook called when server is deleted. Deletes related locks.
	 * 
	 * @param Server $server
	 */
	public static function server_delete(Server $server) {
		$application = $server->application;
		$query = $application->query_delete(__CLASS__)->where('server', $server);
		$query->execute();
		if (($n_rows = $query->affected_rows()) > 0) {
			$application->logger->warning("Deleted {n} {locks} associated with server {name} (#{id})", array(
				"n" => $n_rows,
				"locks" => Locale::plural(__CLASS__, $n_rows)
			) + $server->members());
		}
	}
	
	/**
	 * Break a lock.
	 * PHP5 does not allow functions called "break", PHP7 does.
	 *
	 * @return \zesk\Lock
	 */
	public function crack() {
		$this->pid = $this->server = null;
		return $this->store();
	}
	
	/**
	 * Locked by SOMEONE ELSE
	 */
	public function is_locked() {
		if ($this->member_is_empty('pid') && $this->member_is_empty('server')) {
			return false;
		}
		return $this->_is_locked();
	}
	
	/**
	 * Release a lock I have
	 *
	 * @return Lock
	 */
	public function release() {
		$this->query_update()
			->values(array(
			'pid' => null,
			'server' => null,
			'locked' => null
		))
			->where(array(
			"id" => $this->id
		))
			->execute();
		zesk()->logger->debug("Released lock $this->code");
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
		$lock = $application->orm_factory(__CLASS__, array(
			'code' => $code
		));
		try {
			if (!$lock->find()) {
				$lock->store();
			}
		} catch (Exception_ORM_Duplicate $dup) {
			$lock->find();
		}
		return self::$locks[strtolower($code)] = $lock;
	}
	
	/**
	 * Is this Lock locked by SOMEONE BESIDES MY PROCESS?
	 * 
	 * Logic is as follows:
	 * - If it's registered to another server: We run a hook to check. If no hook, then we assume it's locked.
	 * - Now we assume the lock is on my current server.
	 * - If it's my process ID, then it's not locked by someone else, return false.
	 * - If the other process is still alive, it's locked, return true.
	 * - The other process is dead, so we release the lock. It's no longer alive, return false.
	 * 
	 * @return boolean
	 */
	private function _is_locked() {
		// Each server is responsible for keeping locks clean.
		// Allow a hook to enable inter-server connection, later
		if (!$this->_is_my_server()) {
			return $this->application->hooks->call_arguments(__CLASS__ . '::server_is_locked', array(
				$this->member_integer('server'),
				$this->pid
			), true);
		}
		if ($this->_is_my_pid()) {
			// My process, so it's not locked
			return false;
		}
		// Is the process running?
		if ($this->application->process->alive($this->pid)) {
			return true;
		}
		$this->application->logger->warning("Releasing lock from {server}:{pid} as process is dead", $this->members());
		$this->release();
		return false;
	}
	
	/**
	 * Acquire a lock with an optional where clause
	 *
	 * @param array $where
	 */
	private function _acquire_where(array $where = array()) {
		$update = $this->query_update();
		$sql = $update->sql();
		$update->values(array(
			'pid' => $this->application->process->id(),
			'server' => Server::singleton($this->application),
			'*locked' => $sql->now(),
			'*used' => $sql->now()
		))
			->where(array(
			'id' => $this->id
		) + $where)
			->execute();
		if ($this->fetch()->_is_mine()) {
			zesk()->logger->debug("Acquired lock $this->code");
			return $this;
		}
		return null;
	}
	
	/**
	 * Acquire an inactive lock
	 */
	private function _acquire_once() {
		return $this->_acquire_where(array(
			'pid' => null
		));
	}
	
	/**
	 * Acquire a dead lock, requires that the pid and server don't change between now and
	 * acquisition
	 */
	private function _acquire_dead() {
		return $this->_acquire_where(array(
			"pid" => $this->pid,
			"server" => $this->server
		));
	}
	
	/**
	 * Loop and try to get lock
	 *
	 * @param integer $timeout
	 * @throws Exception_Timeout
	 */
	private function _acquire($timeout) {
		$timer = new Timer();
		// Defaults to 0.5 seconds
		$sleep = $this->option_integer('sleep_seconds', 0.5);
		if ($timeout > 0 && $timeout < $sleep) {
			$sleep = $timeout;
		}
		$sleep = intval($sleep * 1000000);
		$delete_attempt = false;
		while (true) {
			if (!$delete_attempt) {
				self::delete_dead_pids($this->application);
				$delete_attempt = true;
			} else {
				usleep($sleep);
			}
			if ($this->_is_free()) {
				if ($this->_acquire_once()) {
					return $this;
				}
			} else {
				if (!$this->fetch()->_is_locked()) {
					continue;
				}
			}
			if ($timeout > 0 && $timer->elapsed() > $timeout) {
				throw new Exception_Timeout("Waiting for Lock \"{code}\" ({timeout} seconds)", array(
					"method" => __METHOD__,
					"timeout" => $timeout,
					"code" => $this->code
				));
			}
		}
		return null;
	}
	
	/**
	 * Checks if the PID in this Lock is alive
	 *
	 * @return boolean
	 */
	private function is_process_alive() {
		return $this->application->process->alive($this->pid);
	}
	/**
	 * Is this my server?
	 *
	 * @return boolean
	 */
	private function _is_my_server() {
		return Server::singleton($this->application)->id === $this->member_integer('server');
	}
	/**
	 * Does my PID match (DOES NOT MEAN SERVER WILL MATCH)
	 *
	 * @return boolean
	 */
	private function _is_my_pid() {
		return $this->application->process->id() === $this->member_integer('pid');
	}
	
	/**
	 * Is this lock free?
	 *
	 * @return boolean
	 */
	private function _is_free() {
		return $this->member_is_empty('pid') && $this->member_is_empty('server');
	}
	
	/**
	 * Implies PID and server match
	 *
	 * @return boolean
	 */
	private function _is_mine() {
		return $this->_is_my_server() && $this->_is_my_pid();
	}
}
