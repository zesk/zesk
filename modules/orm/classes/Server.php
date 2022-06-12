<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see Server_Data
 * @property int $id
 * @property string $name
 * @property string $name_internal
 * @property string $name_external
 * @property string $ip4_internal
 * @property string $ip4_external
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
	public const DISK_UNITS_BYTES = 'b';

	/**
	 * 1 = 1024^1
	 *
	 * @var string
	 */
	public const DISK_UNITS_KILOBYTES = 'k';

	/**
	 *
	 * @var string
	 */
	public const DISK_UNITS_MEGABYTES = 'm';

	/**
	 * 1 = 1024^2
	 *
	 * @var string
	 */
	public const DISK_UNITS_GIGABYTES = 'g';

	/**
	 * 1 = 1024^3
	 *
	 * @var string
	 */
	public const DISK_UNITS_TERABYTES = 't';

	/**
	 * 1 = 1024^4
	 *
	 * @var string
	 */
	public const DISK_UNITS_PETABYTES = 'p';

	/**
	 * 1 = 1024^5
	 *
	 * @var string
	 */
	public const DISK_UNITS_EXABYTES = 'e';

	/**
	 *
	 * @var array
	 */
	private static $disk_units_list = [
		self::DISK_UNITS_BYTES,
		self::DISK_UNITS_KILOBYTES,
		self::DISK_UNITS_MEGABYTES,
		self::DISK_UNITS_GIGABYTES,
		self::DISK_UNITS_TERABYTES,
		self::DISK_UNITS_PETABYTES,
		self::DISK_UNITS_EXABYTES,
	];

	/**
	 *
	 * @var string
	 */
	public const option_alive_update_seconds = 'alive_update_seconds';

	/**
	 * Number of seconds after which the server status should be updated
	 *
	 * @var integer
	 */
	public const default_alive_update_seconds = 30;

	/**
	 *
	 * @var string
	 */
	public const option_timeout_seconds = 'timeout_seconds';

	/**
	 *
	 * @var int
	 */
	public const default_timeout_seconds = 180;

	/**
	 *
	 * @var Server
	 */
	private static $singleton = null;

	/**
	 * Run once per minute per cluster.
	 * Delete servers who are not alive after `option_timeout_seconds` old.
	 */
	public static function cron_cluster_minute(Application $application): void {
		$server = $application->ormFactory(self::class);
		/* @var $server Server */
		$server->bury_dead_servers();
	}

	/**
	 *
	 * @param Kernel $zesk
	 */
	public static function hooks(Application $zesk): void {
		$zesk->hooks->add(Hooks::HOOK_CONFIGURED, [
			__CLASS__,
			'configured',
		]);
	}

	/**
	 */
	public static function configured(Application $application): void {
		$application->configuration->deprecated('FDISK_PRIMARY', __CLASS__ . '::free_disk_volume');
	}

	/**
	 * Once a minute, update the state
	 *
	 * @param Application $application
	 */
	public static function cron_minute(Application $application): void {
		$server = self::singleton($application);

		try {
			$server->updateState();
		} catch (Exception $e) {
			$application->logger->error("Exception {class} {code} {file}:{line}\n{message}\n{backtrace}", Exception::exception_variables($e));
		}
	}

	/**
	 * Run intermittently once per cluster to clean away dead Server records
	 */
	public function bury_dead_servers(): void {
		$lock = Lock::instance($this->application, __CLASS__ . '::bury_dead_servers');
		if ($lock->acquire() === null) {
			return;
		}
		$query = $this->query_select();
		$pushed = $this->push_utc();

		$timeout_seconds = -abs($this->optionInt('timeout_seconds', self::default_timeout_seconds));
		$dead_to_me = Timestamp::now('UTC')->addUnit($timeout_seconds, Timestamp::UNIT_SECOND);
		$iterator = $query->where([
			'alive|<=' => $dead_to_me,
		])->orm_iterator();
		/* @var $server Server */
		foreach ($iterator as $server) {
			// Delete this way so hooks get called per dead server
			$this->application->logger->warning('Burying dead server {name} (#{id}), last alive on {alive}', $server->members());
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
			throw new Exception_Parameter('No UNAME or HOST defined');
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
					$one_minute_ago = Timestamp::now()->addUnit(-60);
					if ($server->alive instanceof Timestamp && $server->alive->after($one_minute_ago)) {
						return $server;
					}
				}
			}
		}
		$server = $application->ormFactory(__CLASS__);
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
	 */
	protected function _retrieve_singleton() {
		$this->name = self::host_default();

		try {
			if ($this->find()) {
				$now = Timestamp::now();
				if ($now->difference($this->alive) > $this->option(self::option_alive_update_seconds, self::default_alive_update_seconds)) {
					$this->updateState();
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
		$this->call_hook('initialize_names');
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
	private function _initialize_names_defaults(): void {
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
		if (empty($this->ip4_internal) || $this->ip4_internal === '0.0.0.0') {
			$this->ip4_internal = null;
			$ips = System::ip_addresses($this->application);
			$ips = ArrayTools::valuesRemove($ips, ['127.0.0.1']);
			if (count($ips) >= 1) {
				$this->ip4_internal = first(array_values($ips));
			}
		}
		if (empty($this->ip4_internal)) {
			// Probably a single-server system.
			$this->ip4_internal = '127.0.0.1';
		}
		if (empty($this->ip4_external) || $this->ip4_external === '0.0.0.0') {
			// 2018-08-06 No longer inherits $host value, null by default
			$this->ip4_external = null;
		}
	}

	/**
	 * Update server state
	 *
	 * @param string $path
	 * @return self
	 */
	public function updateState(string $path = null): self {
		if ($path === null) {
			$path = $this->option('free_disk_volume', '/');
		}
		$volume_info = System::volume_info();
		$info = $volume_info[$path] ?? null;
		$update = [];
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
		$update['load'] = first(System::load_averages());
		$update['*alive'] = $this->sql()->now();

		try {
			$this->queryUpdate()->setValues($update)->appendWhere($this->members($this->primaryKeys()))->execute();
		} catch (Exception_Semantics $e) {
			// Runtime error - never occur
			$this->application->hooks->call('exception', $e);
		} catch (Database_Exception $e) {
			$this->application->hooks->call('exception', $e);
		}
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
		return in_array(strtolower($tz), [
			'utc',
			'+00:00',
		]);
	}

	/**
	 * Save the Database time zone state, temporarily.
	 */
	private function push_utc() {
		$db = $this->database();
		if ($db->can(Database::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
			$old_tz = $db->timeZone();
			if (!$this->_db_tz_is_utc($old_tz)) {
				// From https://stackoverflow.com/questions/2934258/how-do-i-get-the-current-time-zone-of-mysql#2934271
				// UTC fails on virgin MySQL installations
				// TODO this is (?) specific to MySQL - need to modify for different databases
				$db->setTimeZone('+00:00');
			}
		} else {
			$old_tz = null;
		}
		$old_php_tz = date_default_timezone_get();
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set('UTC');
		}
		return [
			$old_tz,
			$old_php_tz,
		];
	}

	/**
	 * Restore the Database time zone state after the push_utc
	 */
	private function pop_utc(array $pushed): void {
		[$old_tz, $old_php_tz] = $pushed;
		$db = $this->database();
		if ($db->can(Database::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
			if (!$this->_db_tz_is_utc($old_tz)) {
				$db->setTimeZone($old_tz);
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
	 * @return self
	 */
	public function setData(string $name, mixed $value): self {
		$iterator = $this->memberIterator('data', [
			'name' => $name,
		]);
		/* @var $data Server_Data */
		foreach ($iterator as $data) {
			if ($value === null) {
				$data->delete();
				return $this;
			}
			$data->value = $value;
			$data->store();
			return $this;
		}
		if ($value === null) {
			return $this;
		}
		$data = new Server_Data($this->application, [
			'server' => $this,
			'name' => $name,
			'value' => $value,
		]);
		$data->store();
		return $this;
	}

	/**
	 * Get server data object
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return NULL|Server_Data
	 */
	private function _getData(string $name): mixed {
		$iterator = $this->memberIterator('data', [
			'name' => $name,
		]);
		/* @var $data Server_Data */
		foreach ($iterator as $data) {
			return $data->value;
		}
		return null;
	}

	/**
	 * Retrieve or store per-server data
	 *
	 * @param mixed $name
	 * @return mixed
	 * @see Interface_Data::data
	 */
	public function data(string $name): mixed {
		$lock_name = 'server_data_' . $this->memberInteger('id');
		$acquired_lock = $this->database()->getLock($lock_name, 5);
		$result = $this->_getData($name);
		if ($acquired_lock) {
			$this->database()->releaseLock($lock_name);
		}
		return $result;
	}

	/**
	 * Delete a data member of this server.
	 *
	 * @param mixed $name
	 * @return $this
	 * @see Interface_Data::delete_data
	 */
	public function deleteData(string|array $name): self {
		$this->application->ormRegistry(Server_Data::class)->queryDelete()->appendWhere([
			'server' => $this,
			'name' => $name,
		])->execute();
		return $this;
	}

	/**
	 * Delete data members of all servers which match this name.
	 *
	 * @param mixed $name
	 * @return self
	 */
	public function deleteAllData(string $name): self {
		$this->application->ormRegistry(Server_Data::class)->queryDelete()->appendWhere([
			'name' => $name,
		])->execute();
		return $this;
	}

	/**
	 * Query all servers to find servers which match name = value
	 *
	 * @param array $where Use [ "name" => $value } as  basic one
	 * @return Database_Query_Select
	 */
	public function dataQuery(array $where): Database_Query_Select {
		$query = $this->application->ormRegistry(Server::class)->querySelect();
		$query->ormWhat();
		foreach ($where as $name => $value) {
			$alias = "data_$name";
			$query->link(Server_Data::class, [
				'alias' => $alias,
				'on' => [
					'name' => $name,
				],
			]);
			$query->appendWhere([
				"$alias.value" => serialize($value),
			]);
		}
		return $query;
	}

	/**
	 * @param int $within_seconds
	 * @return array
	 * @throws Exception_Parameter
	 */
	public function aliveIPs(int $within_seconds = 300): array {
		$ips = $this->querySelect()->addWhatIterable([
			'ip4_internal' => 'ip4_internal',
			'ip4_external' => 'ip4_extermal',
		])->distinct(true)->appendWhere([
			'alive|>=' => Timestamp::now('UTC')->addUnit(-abs($within_seconds), Timestamp::UNIT_SECOND),
		])->toArray();
		return array_unique(array_merge(ArrayTools::extract($ips, 'ip4_internal'), ArrayTools::extract($ips, 'ip4_external')));
	}
}
