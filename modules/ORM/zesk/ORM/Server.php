<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage file
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use Psr\Cache\InvalidArgumentException;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Database\Base;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\ClassNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\ParseException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\Exception\TimeoutExpired;
use zesk\Exception\Unsupported;
use zesk\Interface\MetaInterface;
use zesk\ORM\Database\Query\Select;
use zesk\ORM\Exception\ORMDuplicate;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Exception\StoreException;
use zesk\System;
use zesk\Timestamp;

/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see Class_Server
 * @see ServerMeta
 * @property int $id
 * @property string $name
 * @property string $name_internal
 * @property string $name_external
 * @property string $ip4_internal
 * @property string $ip4_external
 * @property integer $free_disk
 * @property double $load
 * @property Timestamp $alive
 * @property ORMIterator $metas
 */
class Server extends ORMBase implements MetaInterface {
	public const MEMBER_METAS = 'metas';

	public const DEFAULT_OPTION_FREE_DISK_VOLUME = '/';

	/**
	 *
	 */
	public const OPTION_FREE_DISK_VOLUME = 'free_disk_volume';

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
	private static array $disk_units_list = [
		self::DISK_UNITS_BYTES, self::DISK_UNITS_KILOBYTES, self::DISK_UNITS_MEGABYTES, self::DISK_UNITS_GIGABYTES,
		self::DISK_UNITS_TERABYTES, self::DISK_UNITS_PETABYTES, self::DISK_UNITS_EXABYTES,
	];

	/**
	 *
	 * @var string
	 */
	public const OPTION_ALIVE_UPDATE_SECONDS = 'alive_update_seconds';

	/**
	 * Number of seconds after which the server status should be updated
	 *
	 * @var integer
	 */
	public const DEFAULT_ALIVE_UPDATE_SECONDS = 30;

	/**
	 *
	 * @var string
	 */
	public const OPTION_TIMEOUT_SECONDS = 'timeout_seconds';

	/**
	 *
	 * @var int
	 */
	public const default_timeout_seconds = 180;

	/**
	 * Run once per minute per cluster.
	 * Delete servers who are not alive after `option_timeout_seconds` old.
	 */
	public static function cron_cluster_minute(Application $application): void {
		$server = $application->ormFactory(self::class);
		/* @var $server Server */
		try {
			$server->buryDeadServers();
		} catch (TimeoutExpired) {
		}
	}

	/**
	 * Once a minute, update the state
	 *
	 * @param Application $application
	 */
	public static function cron_minute(Application $application): void {
		try {
			$server = self::singleton($application);
			$server->updateState();
		} catch (Throwable $e) {
			$application->logger->error("Exception {class} {code} {file}:{line}\n{message}\n{backtrace}", Exception::exceptionVariables($e));
		}
	}

	/**
	 * Run intermittently once per cluster to clean away dead Server records
	 * @throws TimeoutExpired
	 */
	public function buryDeadServers(): void {
		try {
			$lock = Lock::instance($this->application, __METHOD__);
		} catch (Throwable $e) {
			throw new TimeoutExpired('Unable to get lock instance {name}', [], 0, $e);
		}
		$lock = $lock->acquire();

		$query = $this->querySelect();
		$pushed = $this->push_utc();

		$timeout_seconds = -abs($this->optionInt(self::OPTION_TIMEOUT_SECONDS, self::default_timeout_seconds));

		try {
			$dead_to_me = Timestamp::now('UTC');
			$dead_to_me->addUnit($timeout_seconds);
		} catch (KeyNotFound|Semantics $e) {
			$this->application->logger->error($e);
			return;
		}
		$iterator = $query->appendWhere([
			'alive|<=' => $dead_to_me,
		])->ormIterator();
		/* @var $server Server */
		foreach ($iterator as $server) {
			// Delete this way so hooks get called per dead server
			try {
				$this->application->logger->warning('Burying dead server {name} (#{id}), last alive on {alive}', $server->members());
				$server->delete();
			} catch (Throwable $t) {
				$this->application->logger->error($t);
				return;
			}
		}
		$this->pop_utc($pushed);
		$lock->release();
	}

	/**
	 * Retrieve the default host name
	 *
	 */
	private static function hostDefault(): string {
		return System::uname();
	}

	/**
	 * Create a singleton for this server.
	 *
	 * Once loaded, is cached for the duration of the process internally.
	 *
	 * Otherwise, stored in a cache for 1 minute.
	 *
	 * @param Application $application
	 * @return Server
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 */
	public static function singleton(Application $application): self {
		$cache = $application->cacheItemPool();
		$item = null;

		try {
			$item = $cache->getItem(__METHOD__);
		} catch (InvalidArgumentException) {
		}
		if ($item && $item->isHit()) {
			$server = $item->get();
			if ($server instanceof Server) {
				$one_minute_ago = Timestamp::now()->addUnit(-60);
				if ($server->alive instanceof Timestamp && $server->alive->after($one_minute_ago)) {
					return $server;
				}
			}
		}
		$server = $application->ormFactory(__CLASS__);
		assert($server instanceof self);
		$server = $server->_findSingleton();
		if ($item) {
			$item->set($server);
			$item->expiresAfter(60);
			$cache->saveDeferred($item);
		}
		return $server;
	}

	/**
	 * Register and load this
	 * @return $this
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 */
	protected function _findSingleton(): self {
		$this->name = self::hostDefault();

		try {
			$orm = $this->find();
			assert($orm instanceof self);
			$now = Timestamp::now();

			$delta = $now->difference($this->alive);
			if ($delta > $this->option(self::OPTION_ALIVE_UPDATE_SECONDS, self::DEFAULT_ALIVE_UPDATE_SECONDS)) {
				$orm->updateState();
			}
			return $this;
		} catch (ORMNotFound) {
			return $this->registerDefaultServer();
		}
	}

	/**
	 *
	 * @return Server
	 * @throws ClassNotFound
	 * @throws StoreException
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 */
	private function registerDefaultServer(): self {
		// Set up our names using hooks (may do nothing)
		$this->callHook('initialize_names');
		// Set all blank values to defaults
		$this->_initializeNameDefaults();

		try {
			$result = $this->store();
		} catch (ORMDuplicate) {
			$result = $this->find();
		}
		assert($result instanceof self);
		return $result;
	}

	/**
	 * Set up some reasonable defaults which define this server relative to other servers
	 */
	private function _initializeNameDefaults(): void {
		$host = self::hostDefault();
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
			$ips = System::ipAddresses($this->application);
			$ips = ArrayTools::valuesRemove($ips, ['127.0.0.1']);
			if (count($ips) >= 1) {
				$this->ip4_internal = ArrayTools::first(array_values($ips));
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
	 * @throws ORMNotFound
	 */
	public function updateState(string $path = ''): self {
		if ($path === '') {
			$path = $this->optionString(self::OPTION_FREE_DISK_VOLUME, self::DEFAULT_OPTION_FREE_DISK_VOLUME);
		}
		$volume_info = System::volumeInfo();
		$info = $volume_info[$path] ?? null;
		$update = [];
		if ($info) {
			$units = self::$disk_units_list;
			$free = $info['free'];
			while ($free > 4294967295 && count($units) > 1) {
				$free = round($free / 1024);
				array_shift($units);
			}
			$update['free_disk'] = $free;
			$update['free_disk_units'] = $units[0];
		}

		$pushed = $this->push_utc();

		try {
			$update['load'] = ArrayTools::first(System::loadAverages());
			$update['*alive'] = $this->sql()->now();
			$this->queryUpdate()->setValues($update)->appendWhere($this->members($this->primaryKeys()))->execute();
			$this->pop_utc($pushed);
			return $this->fetch();
		} catch (Throwable $e) {
			// Runtime error - never occur
			$this->application->hooks->call('exception', $e);

			throw new ORMNotFound(self::class, 'Updating alive', [], $e);
		}
	}

	/**
	 * Handle issue with UTC not being recognized as a valid TZ in virgin MySQL databases.
	 *
	 * Returns whether the database time zone is currently in UTC.
	 *
	 * @param string $tz
	 * @return boolean
	 */
	private function _db_tz_is_utc(string $tz): bool {
		return in_array(strtolower($tz), [
			'utc', '+00:00',
		]);
	}

	/**
	 * Save the Database time zone state, temporarily.
	 */
	private function push_utc(): array {
		$db = $this->database();

		if ($db->can(Base::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
			try {
				$old_tz = $db->timeZone();
				if (!$this->_db_tz_is_utc($old_tz)) {
					// From https://stackoverflow.com/questions/2934258/how-do-i-get-the-current-time-zone-of-mysql#2934271
					// UTC fails on virgin MySQL installations
					// TODO this is (?) specific to MySQL - need to modify for different databases
					$db->setTimeZone('+00:00');
				}
			} catch (Unsupported) {
				// never
				$old_tz = null;
			}
		} else {
			$old_tz = null;
		}
		$old_php_tz = date_default_timezone_get();
		if ($old_php_tz !== 'UTC') {
			date_default_timezone_set('UTC');
		}
		return [
			$old_tz, $old_php_tz,
		];
	}

	/**
	 * Restore the Database time zone state after the push_utc
	 */
	private function pop_utc(array $pushed): void {
		[$old_tz, $old_php_tz] = $pushed;
		$db = $this->database();
		if ($db->can(Base::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP)) {
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
	public function setMeta(string $name, mixed $value): self {
		$iterator = $this->memberIterator(self::MEMBER_METAS, [
			'name' => $name,
		]);
		/* @var $data ServerMeta */
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
		$data = new ServerMeta($this->application, [
			'server' => $this, 'name' => $name, 'value' => $value,
		]);
		$data->store();
		return $this;
	}

	/**
	 * Get server data object
	 *
	 * @param string $name
	 * @return NULL|ServerMeta
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	private function _getMeta(string $name): mixed {
		$iterator = $this->memberIterator(self::MEMBER_METAS, [
			'name' => $name,
		]);
		/* @var $data ServerMeta */
		foreach ($iterator as $data) {
			return $data->value;
		}
		return null;
	}

	/**
	 * Retrieve per-server data
	 *
	 * @param mixed $name
	 * @return mixed
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 * @throws Semantics
	 * @throws TimeoutExpired
	 */
	public function meta(string $name): mixed {
		$lock_name = 'server_data_' . $this->memberInteger('id');
		$this->database()->getLock($lock_name, 5);
		$result = $this->_getMeta($name);
		$this->database()->releaseLock($lock_name);
		return $result;
	}

	/**
	 * Delete a data member of this server.
	 *
	 * @param mixed $name
	 * @return $this
	 * @throws Semantics
	 * @see MetaInterface::delete_data
	 */
	public function deleteMeta(string|array $name): self {
		$this->application->ormRegistry(ServerMeta::class)->queryDelete()->appendWhere([
			'server' => $this, 'name' => $name,
		])->execute();
		return $this;
	}

	/**
	 * Delete data members of all servers which match this name.
	 *
	 * @param mixed $name
	 * @return self
	 */
	/**
	 * @param string $name
	 * @return $this
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function deleteAllMeta(string $name): self {
		$this->application->ormRegistry(ServerMeta::class)->queryDelete()->appendWhere([
			'name' => $name,
		])->execute();
		return $this;
	}

	/**
	 * Query all servers to find servers which match name = value
	 *
	 * @param array $where Use [ "name" => $value } as  basic one
	 */
	public function dataQuery(array $where): Select {
		$query = $this->application->ormRegistry(Server::class)->querySelect();
		$query->ormWhat();
		foreach ($where as $name => $value) {
			$alias = "data_$name";
			$query->link(ServerMeta::class, [
				'alias' => $alias, 'on' => [
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
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public function aliveIPs(int $within_seconds = 300): array {
		$ips = $this->querySelect()->appendWhat([
			'ip4_internal' => 'ip4_internal', 'ip4_external' => 'ip4_extermal',
		])->setDistinct()->appendWhere([
			'alive|>=' => Timestamp::now('UTC')->addUnit(-abs($within_seconds)),
		])->toArray();
		return array_unique(array_merge(ArrayTools::extract($ips, 'ip4_internal'), ArrayTools::extract($ips, 'ip4_external')));
	}
}
