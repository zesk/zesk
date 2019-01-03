<?php

/**
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see Server_Data
 * @property id $id
 * @property string $name
 * @property string $name_internal
 * @property string $name_external
 * @property ip4 $ip4_internal
 * @property ip4 $ip4_external
 * @property integer $free_disk
 * @property integer $free_disk
 * @property double $load
 * @property Timestamp $alive
 */
class Server extends ORM implements Interface_Data {
	/**
	 * 1 = 1024^0
	 *
	 * @var string
	 */
	const DISK_UNITS_BYTES = 'b';

	/**
	 * 1 = 1024^1
	 *
	 * @var string
	 */
	const DISK_UNITS_KILOBYTES = 'k';

	/**
	 *
	 * @var string
	 */
	const DISK_UNITS_MEGABYTES = 'm';

	/**
	 * 1 = 1024^2
	 *
	 * @var string
	 */
	const DISK_UNITS_GIGABYTES = 'g';

	/**
	 * 1 = 1024^3
	 *
	 * @var string
	 */
	const DISK_UNITS_TERABYTES = 't';

	/**
	 * 1 = 1024^4
	 *
	 * @var string
	 */
	const DISK_UNITS_PETABYTES = 'p';

	/**
	 * 1 = 1024^5
	 *
	 * @var string
	 */
	const DISK_UNITS_EXABYTES = 'e';

	/**
	 *
	 * @var array
	 */
	private static $disk_units_list = array(
		self::DISK_UNITS_BYTES,
		self::DISK_UNITS_KILOBYTES,
		self::DISK_UNITS_MEGABYTES,
		self::DISK_UNITS_GIGABYTES,
		self::DISK_UNITS_TERABYTES,
		self::DISK_UNITS_PETABYTES,
		self::DISK_UNITS_EXABYTES,
	);

	/**
	 *
	 * @var string
	 */
	const option_alive_update_seconds = "alive_update_seconds";

	/**
	 * Number of seconds after which the server status should be updated
	 *
	 * @var integer
	 */
	const default_alive_update_seconds = 30;

	/**
	 *
	 * @var string
	 */
	const option_timeout_seconds = "timeout_seconds";

	/**
	 *
	 * @var unknown
	 */
	const default_timeout_seconds = 180;

	/**
	 *
	 * @var Server
	 */
	private static $singleton = null;

	/**
	 * Run once per minute per cluster.
	 * Delete servers who are not alive after `option_timeout_seconds` old.
	 */
	public static function cron_cluster_minute(Application $application) {
		$server = $application->orm_factory(self::class);
		/* @var $server Server */
		$server->bury_dead_servers();
	}

	/**
	 *
	 * @param Kernel $zesk
	 */
	public static function hooks(Application $zesk) {
		$zesk->hooks->add(Hooks::hook_configured, array(
			__CLASS__,
			"configured",
		));
	}

	/**
	 */
	public static function configured(Application $application) {
		$application->configuration->deprecated("FDISK_PRIMARY", __CLASS__ . "::free_disk_volume");
	}

	/**
	 * Once a minute, update the state
	 *
	 * @param Application $application
	 */
	public static function cron_minute(Application $application) {
		$server = self::singleton($application);

		try {
			$server->update_state();
		} catch (Exception $e) {
			$application->logger->error("Exception {class} {code} {file}:{line}\n{message}\n{backtrace}", Exception::exception_variables($e));
		}
	}

	/**
	 * Run intermittently once per cluster to clean away dead Server records
	 */
	public function bury_dead_servers() {
		$lock = Lock::instance($this->application, __CLASS__ . '::bury_dead_servers');
		if ($lock->acquire() === null) {
			return;
		}
		$query = $this->query_select();
		$pushed = $this->push_utc();

		$timeout_seconds = -abs($this->option_integer('timeout_seconds', self::default_timeout_seconds));
		$dead_to_me = Timestamp::now('UTC')->add_unit($timeout_seconds, Timestamp::UNIT_SECOND);
		$iterator = $query->where(array(
			'alive|<=' => $dead_to_me,
		))->orm_iterator();
		/* @var $server Server */
		foreach ($iterator as $server) {
			// Delete this way so hooks get called per dead server
			$this->application->logger->warning("Burying dead server {name} (#{id}), last alive on {alive}", $server->members());
			$server->delete();
		}
		$this->pop_utc($pushed);
		$lock->release();
	}

	/**
	 * Retrieve the default host name
	 *
	 * @throws Exception_Parameter
	 */
	private static function host_default() {
		$host = System::uname();
		if (empty($host)) {
			throw new Exception_Parameter("No UNAME or HOST defined");
		}
		return $host;
	}

	/**
	 * Create a singleton for this server.
	 *
	 * Once loaded, is cached for the duration of the process internally.
	 *
	 * Otherwise, stored in a cache for 1 minute.
	 *
	 * @param Application $application
	 * @return \zesk\Server
	 */
	public static function singleton(Application $application) {
		$cache = $application->cache;
		/* @var $cache \Psr\Cache\CacheItemPoolInterface */
		$server = null;
		if ($cache) {
			$item = $cache->getItem(__METHOD__);
			if ($item->isHit()) {
				$server = $item->get();
				if ($server instanceof Server) {
					return $server;
				}
			}
		}
		$server = $application->orm_factory(__CLASS__);
		$server = $server->_retrieve_singleton();
		if ($cache) {
			$item->set($server);
			$item->expiresAfter(60);
			$cache->saveDeferred($item);
		}
		return $server;
	}

	/**
	 * Register and load this
	 *
	 * @param unknown $host
	 */
	protected function _retrieve_singleton() {
		$this->name = self::host_default();

		try {
			if ($this->find()) {
				$now = Timestamp::now();
				if ($now->difference($this->alive) > $this->option(self::option_alive_update_seconds, self::default_alive_update_seconds)) {
					$this->update_state();
				}
				return $this;
			}
		} catch (Database_Exception_Table_NotFound $e) {
			return null;
		}
		return $this->refresh_names();
	}

	/**
	 *
	 * @return \zesk\Server
	 */
	public function refresh_names() {
		// Set up our names using hooks (may do nothing)
		$this->call_hook("initialize_names");
		// Set all blank values to defaults
		$this->_initialize_names_defaults();

		try {
			return $this->store();
		} catch (Exception_ORM_Duplicate $dup) {
			$this->find();
		}
		return $this;
	}

	/**
	 * Set up some reasonable defaults which define this server relative to other servers
	 */
	private function _initialize_names_defaults() {
		$host = self::host_default();
		if (empty($this->name)) {
			$this->name = $host;
		}
		if (empty($this->name_internal)) {
			$this->name_internal = $host;
		}
		if (empty($this->name_external)) {
			// 2018-08-06 No longer inherits $host value, null by default
			$this->name_external = null;
		}
		if (empty($this->ip4_internal) || $this->ip4_internal === "0.0.0.0") {
			$this->ip4_internal = null;
			$ips = System::ip_addresses($this->application);
			$ips = ArrayTools::remove_values($ips, "127.0.0.1");
			if (count($ips) >= 1) {
				$this->ip4_internal = first(array_values($ips));
			}
		}
		if (empty($this->ip4_internal)) {
			// Probably a single-server system.
			$this->ip4_internal = "127.0.0.1";
		}
		if (empty($this->ip4_external) || $this->ip4_external === "0.0.0.0") {
			// 2018-08-06 No longer inherits $host value, null by default
			$this->ip4_external = null;
		}
	}

	/**
	 * Update server state
	 *
	 * @param unknown $path
	 */
	public function update_state($path = null) {
		if ($path === null) {
			$path = $this->option('free_disk_volume', "/");
		}
		$volume_info = System::volume_info();
		$info = avalue($volume_info, $path);
		$update = array();
		if ($info) {
			$units = self::$disk_units_list;
			$free = $info['free'];
			while ($free > 4294967295 && count($units) > 1) {
				$free = round($free / 1024, 0);
				array_shift($units);
			}
			$update['free_disk'] = $free;
			$update['free_disk_units'] = $units[0];
		}
		$pushed = $this->push_utc();
		$update['load'] = avalue(System::load_averages(), 0, null);
		$update['*alive'] = $this->sql()->now();
		$this->query_update()
			->values($update)
			->where($this->members($this->primary_keys()))
			->execute();
		$this->pop_utc($pushed);
		return $this->fetch();
	}

	/**
	 * Handle issue with UTC not being recognized as a valid TZ in virgin MySQL databases.
	 *
	 * Returns whether the database time zone is currently in UTC.
	 *
	 * @param string $tz
	 * @return boolean
	 */
	private function _db_tz_is_utc($tz) {
		return in_array(strtolower($tz), array(
			"utc",
			"+00:00",
		));
	}

	/**
	 * Save the Database time zone state, temporarily.
	 */
	private function push_utc() {
		$db = $this->database();
		if ($db->can(Database::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
			$old_tz = $db->time_zone();
			if (!$this->_db_tz_is_utc($old_tz)) {
				// From https://stackoverflow.com/questions/2934258/how-do-i-get-the-current-time-zone-of-mysql#2934271
				// UTC fails on virgin MySQL installations
				// TODO this is (?) specific to MySQL - need to modify for different databases
				$db->time_zone('+00:00');
			}
		} else {
			$old_tz = null;
		}
		$old_php_tz = date_default_timezone_get();
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set('UTC');
		}
		return array(
			$old_tz,
			$old_php_tz,
		);
	}

	/**
	 * Restore the Database time zone state after the push_utc
	 */
	private function pop_utc(array $pushed) {
		list($old_tz, $old_php_tz) = $pushed;
		$db = $this->database();
		if ($db->can(Database::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
			if (!$this->_db_tz_is_utc($old_tz)) {
				$db->time_zone($old_tz);
			}
		}
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set($old_php_tz);
		}
	}

	/**
	 * Set or delete the server data object
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return NULL|Server_Data
	 */
	private function set_data($name, $value = null) {
		$iterator = $this->member_iterator("data", array(
			"name" => $name,
		));
		/* @var $data Server_Data */
		foreach ($iterator as $data) {
			if ($value === null) {
				$data->delete();
				return null;
			}
			$data->value = $value;
			return $data->store();
		}
		if ($value === null) {
			return null;
		}
		$data = new Server_Data($this->application, array(
			"server" => $this,
			"name" => $name,
			"value" => $value,
		));
		return $data->store();
	}

	/**
	 * Get server data object
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return NULL|Server_Data
	 */
	private function get_data($name) {
		$iterator = $this->member_iterator("data", array(
			"name" => $name,
		));
		/* @var $data Server_Data */
		foreach ($iterator as $data) {
			return $data->value;
		}
		return null;
	}

	/**
	 * Retrieve or store per-server data
	 *
	 * @see Interface_Data::data
	 * @param mixed $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function data($name, $value = null) {
		$lock_name = 'server_data_' . $this->member_integer('id');
		$acquired_lock = $this->database()->get_lock($lock_name, 5);
		$result = null;
		if (is_array($name)) {
			$result = array();
			foreach ($name as $k => $v) {
				$result[$k] = $this->set_data($k, $v);
			}
		} elseif ($value === null) {
			$result = $this->get_data($name);
		} else {
			$result = $this->set_data($name, $value);
		}
		if ($acquired_lock) {
			$this->database()->release_lock($lock_name);
		} else {
			$this->application->logger->warning("Unable to acquire lock {lock_name}", compact("lock_name"));
		}
		return $result;
	}

	/**
	 * Retrieve or store per-server data
	 *
	 * @see Interface_Data::delete_data
	 * @param mixed $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function delete_data($name) {
		return $this->application->orm_registry(Server_Data::class)
			->query_delete()
			->where(array(
			"server" => $this,
			"name" => $name,
		))
			->execute()
			->affected_rows() > 0;
	}

	/**
	 * Query all servers to find servers which match name = value
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return Database_Query_Select
	 */
	public function data_query($name, $value = null) {
		if (!is_array($name)) {
			$where = array(
				$name => $value,
			);
		} else {
			$where = $name;
		}
		$query = $this->application->orm_registry(Server::class)->query_select();
		$query->what_object();
		foreach ($where as $name => $value) {
			$alias = "data_$name";
			$query->link(Server_Data::class, array(
				"alias" => $alias,
				"on" => array(
					"name" => $name,
				),
			));
			$query->where(array(
				"$alias.value" => serialize($value),
			));
		}
		return $query;
	}
}
