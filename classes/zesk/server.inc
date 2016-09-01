<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/server.inc $
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

use \Lock as Lock;
use \system as system;
use \Exception_Parameter as Exception_Parameter;
use \Database_Exception_Table_NotFound as Database_Exception_Table_NotFound;
use \Exception_Object_Duplicate as Exception_Object_Duplicate;
use \zesk as zesk;
use \Database as Database;
use \Server_Data as Server_Data;

/**
 * zesk\Server
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
 * @property double $load
 * @property Timestamp $alive
 */
abstract class Server extends Object implements Interface_Data {
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
		$server = $application->object_factory("Server");
		/* @var $server Server */
		$server->bury_dead_servers();
	}
	
	/**
	 * Once a minute, update the state
	 *
	 * @param Application $application        	
	 */
	public static function cron_minute(Application $application) {
		$server = self::singleton();
		$server->update_state();
	}
	
	/**
	 * Run intermittently once per cluster to clean away dead Server records
	 */
	public function bury_dead_servers() {
		if (($lock = Lock::get_lock(__CLASS__ . '::bury_dead_servers')) !== null) {
			$query = $this->query();
			$pushed = $this->push_utc();
			
			$dead_to_me = Timestamp::now('UTC')->add_unit("second", -abs($this->option_integer('timeout_seconds', self::default_timeout_seconds)));
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
	
	/**
	 * Retrieve the default host name
	 *
	 * @throws Exception_Parameter
	 */
	private static function host_default() {
		$host = system::uname();
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
	 * @param string $host
	 *        	Use the alternate host provided
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
		$server = Application::instance()->object_factory("Server");
		return self::$singleton = $cache->server = $server->_retrieve_singleton();
	}
	protected function refresh_names() {
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
		$this->hook("initialize_names");
		$this->_initialize_names_defaults();
		try {
			return $this->store();
		} catch (Exception_Object_Duplicate $dup) {
			$this->find();
		}
		return $this;
	}
	/**
	 *
	 * @param unknown $host        	
	 */
	private function _initialize_names_defaults() {
		$host = self::host_default();
		if (!isset($this->name)) {
			$this->name = $host;
		}
		if (!isset($this->name_internal)) {
			$this->name_internal = $host;
		}
		if (!isset($this->name_external)) {
			$this->name_external = $host;
		}
		if (!isset($this->ip4_internal)) {
			$ips = system::ip_addresses();
			$ips = arr::remove_values($ips, "127.0.0.1");
			if (count($ips) >= 1) {
				$this->ip4_internal = first(array_values($ips));
			}
		}
	}
	
	/**
	 * Update server state
	 *
	 * @param unknown $path        	
	 */
	function update_state($path = null) {
		if ($path === null) {
			global $zesk;
			/* @var $zesk zesk\Kernel */
			$path = $zesk->configuration->get('FDISK_PRIMARY', "/"); // TODO Consider renaming this
		}
		$volume_info = system::volume_info();
		$info = avalue($volume_info, $path);
		$update = array();
		if ($info) {
			$update['free_disk'] = $info['free'];
		}
		$pushed = $this->push_utc();
		$update['load'] = avalue(system::load_averages(), 0, null);
		$update['*alive'] = $this->sql()->now();
		$this->query_update()->values($update)->where($this->members($this->primary_keys()))->execute();
		$this->pop_utc($pushed);
		return $this->fetch();
	}
	
	/**
	 * Save the Database time zone state, temporarily.
	 */
	private function push_utc() {
		$db = $this->database();
		if ($db->can(Database::feature_time_zone_relative_timestamp)) {
			$old_tz = $db->time_zone();
			if ($old_tz !== 'UTC') {
				$db->time_zone('UTC');
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
			$old_php_tz
		);
	}
	
	/**
	 * Restore the Database time zone state after the push_utc
	 */
	private function pop_utc(array $pushed) {
		list($old_tz, $old_php_tz) = $pushed;
		$db = $this->database();
		if ($db->can(Database::feature_time_zone_relative_timestamp)) {
			if ($old_tz !== 'UTC') {
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
	 * @return boolean
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

