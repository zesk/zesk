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
 * Support locks across processes, servers. Specific to the database, however.
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
class Lock extends Object {
	private static $locks = array();
	
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
		$server_ids = $application->query_select("zesk\\Server")->to_array(null, "id");
		$iterator = $application->query_select(__CLASS__)->where("X.server|!=|AND", $server_ids)->object_iterator();
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
		global $zesk;
		/* @var $zesk Kernel */
		$timeout_seconds = -abs($zesk->configuration->path_get("Lock::timeout_seconds", 100));
		$you_are_dead_to_me = Timestamp::now()->add_unit($timeout_seconds, Timestamp::UNIT_SECOND);
		$iterator = $application->query_select(__CLASS__)->where(array(
			'server' => Server::singleton(),
			'locked|<=' => $you_are_dead_to_me
		))->object_iterator();
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
	 * Get a lock or throw an Exception_Lock
	 *
	 * @param string $code
	 * @param integer $timeout Optional timeout
	 * @throws Exception_Lock
	 * @return Lock
	 */
	public static function require_lock($code, $timeout = null) {
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
	 * $lock = Lock::get_lock("foo", 1); // Tries to get lock for one second, then throws Exception_Timeout
	 * </code>
	 *
	 * @param string $code
	 * @param double $timeout Time, in seconds, to wait until
	 * @return Lock|null
	 */
	public static function get_lock($code, $timeout = null) {
		zesk()->hooks->register_class(__CLASS__);
		$lock = self::_get_lock($code);
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
	public static function release_all() {
		global $zesk;
		
		/* @var $zesk Kernel */
		self::$locks = array();
		try {
			app()->query_update(__CLASS__)->values(array(
				'pid' => null,
				'server' => null,
				'locked' => null
			))->where(array(
				'pid' => $zesk->process_id(),
				'server' => Server::singleton()
			));
		} catch (Exception $e) {
			// Ignore for now - likely database misconfigured
		}
	}
	public static function server_delete(Server $server) {
		$query = app()->query_delete(__CLASS__)->where('server', $server);
		$query->execute();
		if (($n_rows = $query->affected_rows()) > 0) {
			zesk()->logger->warning("Deleted {n} {locks} associated with server {name} (#{id})", array(
				"n" => $n_rows,
				"locks" => Locale::plural(__CLASS__, $n_rows)
			) + $server->members());
		}
	}
	/**
	 * Register all zesk hooks
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add('Server::delete', array(
			__CLASS__,
			'server_delete'
		));
		$zesk->hooks->add('exit', array(
			__CLASS__,
			"release_all"
		));
	}
	
	/**
	 * Break a lock. PHP5 does not allow functions called "break", PHP7 does.
	 * 
	 * @param unknown $code
	 * @return NULL|\zesk\Lock
	 */
	public static function crack($code) {
		$lock = self::_get_lock($code);
		if (!$lock) {
			return null;
		}
		$lock->pid = $lock->server = null;
		return $lock->store();
	}
	
	/**
	 * Locked by SOMEONE ELSE
	 * @param unknown $code
	 */
	public static function is_locked($code) {
		$lock = self::_get_lock($code);
		if ($lock->member_is_empty('pid') && $lock->member_is_empty('server')) {
			return false;
		}
		return $lock->_is_locked();
	}
	private function _is_locked() {
		global $zesk;
		/* @var $zesk Kernel */
		// Each server is responsible for keeping locks clean.
		// Allow a hook to enable inter-server connection, later
		if (!$this->_is_my_server()) {
			return $this->application->hooks->call_arguments('Lock::server_is_locked', array(
				$this->member_integer('server'),
				$this->pid
			), true);
		}
		if ($this->_is_my_pid()) {
			// My process, so it's not locked
			return false;
		}
		// Is the process running?
		if ($zesk->process->alive($this->pid)) {
			return true;
		}
		zesk()->logger->warning("Releasing lock from {server}:{pid} as process is dead", $this->members());
		$this->release();
		return false;
	}
	
	/**
	 * Release a lock I have
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
	private static function _create_lock($code) {
		$lock = $lock = Object::factory(__CLASS__, array(
			'code' => $code
		));
		try {
			if (!$lock->find()) {
				$lock->store();
			}
		} catch (Exception_Object_Duplicate $dup) {
			$lock->find();
		}
		return self::$locks[strtolower($code)] = $lock;
	}
	
	/**
	 * Retrieve the cached version of a lock or register one
	 * 
	 * @param string $code
	 * @return Lock
	 */
	private static function _get_lock($code) {
		/* @var $lock Lock */
		$lock = avalue(self::$locks, strtolower($code));
		if (!$lock) {
			$lock = self::_create_lock($code);
		} else if (!$lock->_is_mine()) {
			try {
				$lock->fetch();
			} catch (Exception_Object_NotFound $e) {
				// Has since been deleted
				$lock = self::_create_lock($code);
			}
		}
		return $lock;
	}
	
	/**
	 * Acquire a lock with an optional where clause
	 * @param array $where
	 */
	private function _acquire_where(array $where = array()) {
		global $zesk;
		/* @var $zesk Kernel */
		$update = $this->query_update();
		$sql = $update->sql();
		$update->values(array(
			'pid' => $zesk->process->id(),
			'server' => Server::singleton(),
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
	 * Acquire a dead lock, requires that the pid and server don't change between now and acquisition
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
					"function" => "Lock::get_lock",
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
		global $zesk;
		/* @var $zesk Kernel */
		return $zesk->process->alive($this->pid);
	}
	/**
	 * Is this my server?
	 *
	 * @return boolean
	 */
	private function _is_my_server() {
		return Server::singleton()->id === $this->member_integer('server');
	}
	/**
	 * Does my PID match (DOES NOT MEAN SERVER WILL MATCH)
	 *
	 * @return boolean
	 */
	private function _is_my_pid() {
		global $zesk;
		/* @var $zesk Kernel */
		return $zesk->process->id() === $this->member_integer('pid');
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
