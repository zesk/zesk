<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/server.inc $
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
/**
 * Zesk_Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see Server_Data
 * @package zesk
 * @subpackage file
 *
 * @see Lock
 */
abstract class Zesk_Server extends Object implements Interface_Data {

	/**
	 * Run once per minute per cluster
	 */
	public static function cron_cluster_minute() {
		$server = new Server();
		$server->bury_dead_servers();
	}

	/**
	 * Run intermittently once per cluster to clean away dead Server records
	 */
	public function bury_dead_servers() {
		if (($lock = Lock::get_lock(__CLASS__ . '::bury_dead_servers')) !== null) {
			$query = Object::class_query('Server');
			$pushed = $this->push_utc();

			$dead_to_me = Timestamp::now()->add_unit("second", -abs(zesk::geti('Server::timeout_seconds', 180)));
			$iterator = $query->where(array(
				'alive|<=' => $dead_to_me
			))->object_iterator();
			/* @var $server Server */
			foreach ($iterator as $server) {
				// Delete this way so hooks get called per dead server
				log::warning("Burying dead server {name} (#{id}), last alive on {alive}", $server->members());
				$server->delete();
			}
			$this->pop_utc($pushed);
			$lock->release();
		}
	}
	private static function host_default() {
		$host = system::uname();
		if (empty($host)) {
			throw new Exception_Parameter("No HOST defined");
		}
		return $host;
	}
	private static $singleton = null;

	/**
	 * Create a singleton for this server.
	 *
	 * Once loaded, is cached for the duration of the process internally.
	 *
	 * Otherwise, stored in a cache for 1 minute.
	 *
	 * @param string $host Use the alternate host provided
	 * @return Server
	 */
	public static function singleton($host = null) {
		if (self::$singleton) {
			return self::$singleton;
		}
		$cache = Cache::register(__METHOD__)->expire_after(60);
		$server = $cache->server;
		if ($server instanceof Server) {
			return $server;
		}
		$server = new Server();
		if (empty($host)) {
			$host = self::host_default();
		}
		$server->name = str::left($host, '.', $host);
		$lock = "Server_singleton";
		$server_db = Object::cache_object('Server')->database();
		if (!$server_db->get_lock($lock, 5)) {
			throw new Exception("Can not get Server_singleton lock for $host - too many servers registering at once?");
		}
		if ($server->find()) {
			$server->updateState();
			$server_db->release_lock($lock);
		} else {
			$server->updatenames($host);
			$result = $server->store();
			$server_db->release_lock($lock);
			if (!$result) {
				return null;
			}
		}
		$cache->server = $server;
		return self::$singleton = $server;
	}

	public function updatenames($host = null) {
		if ($host === null) {
			$host = self::host_default();
		}
		// 		$ip_map = cluster::hosts(array(
		// 			$host,
		// 			"WEB_SERVER",
		// 			"LAN"
		// 		));
		// 		if (count($ip_map) !== 0) {
		// 			$ip = key($ip_map);
		// 			$this->ip4_internal = $ip;
		// 			$this->name_internal = $ip_map[$ip];
		// 		} else {
		$ifconfig = system::ifconfig("inet;ether");
		$ip = "127.0.0.1";
		foreach ($ifconfig as $interface => $values) {
			$ip = avalue(array_keys($values['inet']), 0);
			break;
		}
		$this->ip4_internal = $ip;
		$this->name_internal = $host;
		$this->name_external = $host;

		// 		}
		// 		$ip_map = cluster::hosts(array(
		// 			$host,
		// 			"WEB_SERVER",
		// 			"WAN"
		// 		));
		// 		if (count($ip_map) !== 0) {
		// 			$ip = key($ip_map);
		// 			$this->ip4_external = $ip;
		// 			$this->name_external = $ip_map[$ip];
		// 		} else {
		// 			$this->ip4_external = "0.0.0.0";
		// 			$this->name_external = $host;
		// 		}
	}
	function updateState($path = null) {
		if ($path === null) {
			$path = zesk::get('FDISK_PRIMARY', "/"); // TODO Consider renaming this
		}
		$volume_info = system::volume_info();
		$info = avalue($volume_info, $path);
		$update = array();
		if ($info) {
			$update['free_disk'] = $info['free'];
		}
		$update['load'] = avalue(system::load_averages(), 0, null);
		$update['*alive'] = $this->sql()->now();
		$pushed = $this->push_utc();
		$this->query_update()->values($update)->where($this->members($this->primary_keys()))->execute();
		$this->pop_utc($pushed);
	}

	private function pop_utc(array $pushed) {
		list($old_tz, $old_php_tz) = $pushed;
		if ($old_tz !== 'UTC') {
			$this->database()->query("SET time_zone='$old_tz'");
		}
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set($old_php_tz);
		}

	}
	private function push_utc() {
		$old_tz = $this->database()->query_one("SELECT @@time_zone as tz", "tz", "UTC");
		$old_php_tz = date_default_timezone_get();
		if ($old_tz !== 'UTC') {
			$this->database()->query("SET time_zone='UTC'");
		}
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set('UTC');
		}
		return array(
			$old_tz,
			$old_php_tz
		);
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
			"name" => $name
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
		$data = new Server_Data(array(
			"server" => $this,
			"name" => $name,
			"value" => $value
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
			"name" => $name
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
	function data($name, $value = null) {
		$lock_name = 'server_data_' . $this->member_integer('id');
		$acquired_lock = $this->database()->get_lock($lock_name, 5);
		$result = null;
		if (is_array($name)) {
			$result = array();
			foreach ($name as $k => $v) {
				$result[$k] = $this->set_data($k, $v);
			}
		} else if ($value === null) {
			$result = $this->get_data($name);
		} else {
			$result = $this->set_data($name, $value);
		}
		if ($acquired_lock) {
			$this->database()->release_lock($lock_name);
		} else {
			log::warning("Unable to acquire lock {lock_name}", compact("lock_name"));
		}
		return $result;
	}

	/**
	 * Retrieve or store per-server data
	 *
	 * @see Interface_Data::delete_data
	 * @param mixed $name
	 * @param mixed $value
	 * @return mixed
	 */
	function delete_data($name) {
		return Object::class_query_delete("Server_Data")->where(array(
			"server" => $this,
			"name" => $name
		))->execute()->affected_rows() > 0;
	}

	/**
	 * Query all servers to find servers which match name = value
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return Database_Query_Select
	 */
	public static function data_query($name, $value = null) {
		if (!is_array($name)) {
			$where = array(
				$name => $value
			);
		} else {
			$where = $name;
		}
		$query = Object::class_query("Server");
		$query->what_object();
		foreach ($where as $name => $value) {
			$alias = "data_$name";
			$query->link("Server_Data", array(
				"alias" => $alias,
				"on" => array(
					"name" => $name
				)
			));
			$query->where(array(
				"$alias.value" => serialize($value)
			));
		}
		return $query;
	}
}

