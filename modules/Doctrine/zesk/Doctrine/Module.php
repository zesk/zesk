<?php
declare(strict_types=1);
/**
 * February 2023 added Doctrine for database and ORM
 *
 * @package zesk
 * @subpackage Doctrine
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use zesk\Types;
use zesk\CacheItemPool\FileCacheItemPool;
use zesk\Directory;
use zesk\Doctrine\Types\EnumBoolean;
use zesk\Doctrine\Types\Timestamp;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\Unsupported;
use zesk\Module as BaseModule;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\MalformedDsnException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\Types\Type;

class Module extends BaseModule {
	/**
	 *
	 */
	public const OPTION_SOURCE_PATHS = 'paths';

	/**
	 *
	 */
	private Configuration $ormConfig;

	private EventManager $eventManager;

	/**
	 * @var array<string, EntityManager>
	 */
	private array $managers = [];

	private static array $zeskTypes = [
		Timestamp::TYPE => Timestamp::class,
		EnumBoolean::TYPE => EnumBoolean::class,
	];

	private static bool $added = false;

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws Exception
	 * @throws Unsupported
	 */
	public function initialize(): void {
		parent::initialize();
		if (!self::$added) {
			foreach (self::$zeskTypes as $type => $class) {
				Type::addType($type, $class);
			}
			self::$added = true;
		}
		$this->eventManager = new EventManager();
		$this->application->registerManager('entity', $this->entityManager(...));
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function addPath(string $path): self {
		$paths = $this->optionArray(self::OPTION_SOURCE_PATHS);
		if (!in_array($path, $paths)) {
			$paths[] = $path;
		}
		$this->setOption(self::OPTION_SOURCE_PATHS, $paths);
		return $this;
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws Exception
	 */
	public function configureORM(): void {
		$this->inheritConfiguration();
		$paths = Types::toList($this->optionIterable(self::OPTION_SOURCE_PATHS, ['./src']));
		$paths[] = $this->path('zesk/Doctrine');
		$paths = array_map($this->application->paths->expand(...), $paths);
		foreach ($paths as $path) {
			$this->application->logger->info('ORM Path: {path}', ['path' => $path]);
		}
		$cachePath = Directory::depend($this->application->cachePath('doctrine'));
		$this->ormConfig = ORMSetup::createAttributeMetadataConfiguration(paths: $paths, isDevMode: $this->application->development(), cache: new FileCacheItemPool($cachePath));
		$this->_setupConnections();
	}

	/**
	 * @throws ConfigurationException
	 * @throws Exception
	 */
	private function _setupConnections(): void {
		foreach ($this->optionArray('connections') as $name => $dsn) {
			if (is_string($dsn)) {
				$parser = new DsnParser();

				try {
					$parts = $parser->parse($dsn);
				} catch (MalformedDsnException $e) {
					throw new ConfigurationException([
						self::class, 'connections', $name,
					], 'Invalid URL {dsn}', ['dsn' => $dsn], $e);
				}
			} elseif (is_array($dsn)) {
				$parts = $dsn;
			} else {
				throw new ConfigurationException([
					self::class, 'connections', $name,
				], 'Bad configuration {type}', ['type' => Types::type($dsn)]);
			}
			$connection = DriverManager::getConnection($parts, $this->ormConfig, $this->eventManager);
			foreach (array_keys(self::$zeskTypes) as $type) {
				$connection->getDatabasePlatform()->registerDoctrineTypeMapping('db_' . $type, $type);
			}

			try {
				$this->managers[$name] = new EntityManager($connection, $this->ormConfig, $this->eventManager);
			} catch (MissingMappingDriverImplementation $e) {
				throw new ConfigurationException([
					self::class, 'connections', $name,
				], 'Missing driver {parts}', ['parts' => $parts]);
			}
		}
	}

	private function defaultEntityManager(): string {
		return $this->optionString('default', 'default');
	}

	/**
	 * @param string $name
	 * @return EntityManager
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws Exception
	 * @throws NotFoundException
	 */
	public function entityManager(string $name = ''): EntityManager {
		if ($name === '') {
			$name = $this->defaultEntityManager();
		}
		if (count($this->managers) === 0) {
			// Lazy setup. May not be the best idea.
			$this->configureORM();
		}
		if (array_key_exists($name, $this->managers)) {
			return $this->managers[$name];
		}

		throw new NotFoundException('No entityManager with name {name}', ['name' => $name]);
	}
}
