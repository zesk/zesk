<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage file
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\OptimisticLockException;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Cron\Attributes\Cron;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\Name;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;
use zesk\Interface\MetaInterface;
use zesk\IPv4;
use zesk\System;
use zesk\Timestamp;

/**
 * Server
 *
 * Represents a server (virtual or physical)
 *
 * @see ServerMeta
 */
#[Entity]
class Server extends Model implements MetaInterface {
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

	use AutoID;
	use Name;

	#[Column(type: 'string', length: 64, nullable: false)]
	public string $nameInternal = '';

	#[Column(type: 'string', length: 64, nullable: false)]
	public string $nameExternal = '';

	#[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
	public int $IP4Internal = 0;

	#[Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
	public int $IP4External = 0;

	#[Column(type: 'integer', nullable: false)]
	public int $freeDisk = 0;

	#[Column(type: 'string', length: 1, nullable: false)]
	public string $freeDiskUnits = self::DISK_UNITS_BYTES;

	#[Column(type: 'float', nullable: false)]
	public float $loadAverage = 0.0;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $alive;

	#[OneToMany(mappedBy: 'server', targetEntity: ServerMeta::class, cascade: ['all'], indexBy: 'name')]
	public Collection $metas;

	public function initialize(): void {
		parent::initialize();
		$this->alive = Timestamp::nowUTC();
		$this->metas = new ArrayCollection();
	}

	/**
	 * Run once per minute per cluster.
	 * Delete servers who are not alive after `option_timeout_seconds` old.
	 */
	#[Cron(schedule: '* * * * *', scope: Cron::SCOPE_APPLICATION)]
	public static function cronClusterMinute(Application $application): void {
		$server = new self($application);
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
	#[Cron(schedule: '* * * * *', scope: Cron::SCOPE_SERVER)]
	public static function cronMinute(Application $application): void {
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

		$em = $this->application->entityManager();
		$builder = $em->createQueryBuilder();

		$timeout_seconds = -abs($this->optionInt(self::OPTION_TIMEOUT_SECONDS, self::default_timeout_seconds));

		try {
			$dead_to_me = Timestamp::now('UTC');
			$dead_to_me->addUnit($timeout_seconds);
		} catch (KeyNotFound|SemanticsException $e) {
			$this->application->logger->error($e);
			return;
		}
		$query = $builder->select()->from(self::class, 'X')->where('X.alive < :deadToMe')->setParameter('deadToMe', $dead_to_me)->getQuery();
		/* @var $server Server */
		foreach ($query->toIterable() as $server) {
			/* @var $server Server */
			// Delete this way so hooks get called per dead server
			try {
				$this->application->logger->warning('Burying dead server {name} (#{id}), last alive on {alive}', $server->variables());
				$server->delete();
			} catch (Throwable $t) {
				$this->application->logger->error($t);
				return;
			}
		}

		try {
			$em->flush();
		} catch (OptimisticLockException|ORMException $e) {
			$this->application->logger->error($e->getMessage());
		}
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
	 */
	public static function singleton(Application $application): self {
		return (new self($application))->_findSingleton();
	}

	/**
	 * Register and load this
	 * @return $this
	 * @throws ORMException
	 */
	protected function _findSingleton(): self {
		$server = $this->em->getRepository(self::class)->findOneBy([
			'name' => self::hostDefault(),
		]);
		if (!$server) {
			return $this->registerDefaultServer()->updateState();
		}
		assert($server instanceof self);
		$delta = Timestamp::now()->difference($server->alive);
		if ($delta > $this->option(self::OPTION_ALIVE_UPDATE_SECONDS, self::DEFAULT_ALIVE_UPDATE_SECONDS)) {
			return $server->updateState();
		}
		return $server;
	}

	public const HOOK_INITIALIZE_NAME = __CLASS__ . '::initializeNames';

	/**
	 *
	 * @return self
	 */
	private function registerDefaultServer(): self {
		// Set up our names using hooks (may do nothing)
		$this->invokeHooks(self::HOOK_INITIALIZE_NAME);
		// Set all blank values to defaults
		$this->_initializeNameDefaults();
		return $this;
	}

	/**
	 * Set up some reasonable defaults which define this server relative to other servers
	 */
	private function _initializeNameDefaults(): void {
		if (!$this->name) {
			$this->name = self::hostDefault();
		}
		if (!$this->nameInternal) {
			$this->nameInternal = self::hostDefault();
		}
		if (!$this->nameExternal) {
			// 2018-08-06 No longer inherits $host value, null by default
			$this->nameExternal = '';
		}
		if (!$this->IP4Internal) {
			$ips = System::ipAddresses($this->application);
			$ips = ArrayTools::valuesRemove($ips, ['127.0.0.1']);
			if (count($ips) >= 1) {
				$this->IP4Internal = IPv4::toInteger(ArrayTools::first(array_values($ips)));
			}
			if (!$this->IP4Internal) {
				// Probably a single-server system.
				$this->IP4Internal = ip2long('127.0.0.1');
			}
		}
	}

	/**
	 * Update server state
	 *
	 * @param string $path
	 * @return self
	 * @throws ORMException
	 */
	public function updateState(string $path = ''): self {
		if ($path === '') {
			$path = $this->optionString(self::OPTION_FREE_DISK_VOLUME, self::DEFAULT_OPTION_FREE_DISK_VOLUME);
		}
		$volume_info = System::volumeInfo();
		$info = $volume_info[$path] ?? null;
		if ($info) {
			$units = self::$disk_units_list;
			$free = $info['free'];
			while ($free > 4294967295 && count($units) > 1) {
				$free = round($free / 1024);
				array_shift($units);
			}
			$this->freeDisk = intval($free);
			$this->freeDiskUnits = $units[0];
		}

		try {
			$this->loadAverage = ArrayTools::first(System::loadAverages());
		} catch (Throwable) {
		}
		$this->alive = Timestamp::nowUTC();
		$this->em->persist($this);
		return $this;
	}

	/**
	 * Set or delete the server data object
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 * @throws ORMException
	 */
	public function setMeta(string $name, mixed $value): self {
		ServerMeta::register($this, $name, $value);
		return $this;
	}

	/**
	 * Get server data object
	 *
	 * @param string $name
	 * @throws KeyNotFound
	 */
	private function _getMeta(string $name): mixed {
		$meta = $this->em->getRepository(ServerMeta::class)->findOneBy(['server' => $this, 'name' => $name]);
		if (!$meta) {
			throw new KeyNotFound($name);
		}
		return $meta->value;
	}

	/**
	 * Retrieve per-server data
	 *
	 * @param mixed $name
	 * @return mixed
	 * @throws KeyNotFound
	 */
	public function meta(string $name): mixed {
		return $this->_getMeta($name);
	}

	/**
	 * Delete a data member of this server.
	 *
	 * @param mixed $name
	 * @return $this
	 * @throws ORMException
	 * @see MetaInterface::delete_data
	 */
	public function deleteMeta(string|array $name): self {
		$meta = $this->em->getRepository(ServerMeta::class)->findOneBy(['server' => $this, 'name' => $name]);
		if ($meta) {
			$this->em->remove($meta);
		}
		return $this;
	}

	/**
	 *
	 * @return $this
	 * @throws ORMException
	 */
	public function clearMeta(): self {
		$this->em->createQuery('DELETE FROM ' . ServerMeta::class . ' WHERE server=:id')->execute(['id' => $this->id]);
		$this->em->getRepository(ServerMeta::class)->clear();
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
	 */
	public function deleteAllMeta(string $name): self {
		$metas = $this->em->getRepository(ServerMeta::class)->findBy(['name' => $name]);
		$this->em->remove($metas);
		return $this;
	}

	/**
	 * @param int $within_seconds
	 * @return array
	 */
	public function aliveIPs(int $within_seconds = 300): array {
		$builder = $this->em->createQueryBuilder();
		$query = $builder->select([
			'ip4_internal', 'ip4_external',
		])->from(self::class, 'X')->where('X.alive > DATE_SUB(CURRENT_TIMESTAMP(), :delta, \'second\')')->setParameter('delta', $within_seconds)->getQuery();
		$ips = [];
		foreach ($query->toIterable(hydrationMode: AbstractQuery::HYDRATE_ARRAY) as $row) {
			$ips[$row['ip4_internal']] = true;
			$ips[$row['ip4_external']] = true;
		}
		return array_map(IPv4::fromInteger(...), array_keys($ips));
	}
}
