<?php
declare(strict_types=1);
/**
 * February 2023 added Doctrine for database and ORM
 *
 * @package    zesk
 * @subpackage Doctrine
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\MalformedDsnException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Exception\TypesAlreadyExists;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use zesk\Application as BaseApplication;
use zesk\CacheItemPool\FileCacheItemPool;
use zesk\Command\Info;
use zesk\Directory;
use zesk\Doctrine\Types\EnumBoolean;
use zesk\Doctrine\Types\Timestamp;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\UnsupportedException;
use zesk\FilterMethod;
use zesk\Kernel;
use zesk\Module as BaseModule;
use zesk\Types;
use zesk\URL;

class Module extends BaseModule
{

    /**
     *
     */
    public const OPTION_CONNECTIONS = 'connections';

    /**
     * @todo Call this somewhere
     */
    public const HOOK_SCHEMA_UPDATED = self::class . '::schemaUpdated';

    /**
     *
     */
    public const OPTION_SOURCE_HOMES = 'paths';

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

    /**
     * @return void
     * @throws ConfigurationException
     * @throws Exception
     * @throws UnsupportedException
     */
    public function initialize(): void
    {
        parent::initialize();
        foreach (self::$zeskTypes as $type => $class) {
            try {
                Type::addType($type, $class);
            } catch (TypesAlreadyExists) {
            }
        }
        $this->eventManager = new EventManager($this->application);
        $this->application->registerManager('entity', $this->entityManager(...));
    }

    /**
     * @param EntityManager $em
     * @return BaseApplication
     */
    public static function entityManagerApplication(EntityManager $em): BaseApplication
    {
        $eventManager = $em->getEventManager();
        if ($eventManager instanceof EventManager) {
            return $eventManager->application;
        }
        return Kernel::wakeupApplication();
    }

    #[FilterMethod(handles: Info::FILTER_INFO)]
    public function info(array $info): array
    {
        $info['doctrine'] = true;
        return $info;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function addPath(string $path): self
    {
        $paths = $this->optionArray(self::OPTION_SOURCE_HOMES);
        if (!in_array($path, $paths)) {
            $paths[] = $path;
        }
        $this->setOption(self::OPTION_SOURCE_HOMES, $paths);
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
    public function configureORM(): void
    {
        $this->inheritConfiguration();
        $paths = Types::toList($this->optionIterable(self::OPTION_SOURCE_HOMES, ['./src']));
        $paths = array_map($this->application->paths->expand(...), $paths);
        $cachePath = Directory::depend($this->application->cachePath('doctrine'));
        $config = $this->ormConfig = ORMSetup::createAttributeMetadataConfiguration(paths: $paths, isDevMode: $this->application->development(), cache: new FileCacheItemPool($cachePath));
        $config->setEntityListenerResolver(new EntityListenerResolver());
        $this->openConnections();
    }

    /**
     * @return array
     */
    public function optionConnections(): array
    {
        return $this->optionArray(self::OPTION_CONNECTIONS);
    }

    /**
     * @return void
     * @throws ConfigurationException
     * @throws Exception
     */
    public function openConnections(): void
    {
        foreach ($this->optionConnections() as $name => $dsn) {
            if ($manager = $this->managers[$name] ?? null) {
                if ($manager->isOpen()) {
                    continue;
                }
                $manager->clear();
            }
            $this->managers[$name] = $this->_connectDSN($dsn, $name);
        }
    }

    /**
     * @param string $name
     * @return string
     * @throws ConfigurationException
     * @throws NotFoundException
     * @throws UnsupportedException
     */
    public function dsnFromName(string $name): string
    {
        $connections = $this->optionConnections();
        if (!isset($connections[$name])) {
            throw new NotFoundException('No such name {name}', ['name' => $name]);
        }
        return self::dsnFromMixed($connections[$name]);
    }

    /**
     * @param mixed $dsn
     * @return string
     * @throws ConfigurationException
     * @throws UnsupportedException
     */
    private function dsnFromMixed(mixed $dsn): string
    {
        if (is_string($dsn)) {
            return $dsn;
        } elseif (is_array($dsn)) {
            $dsn['scheme'] = $dsn['scheme'] ?? $dsn['driver'] ?? null;
            $requiredKeys = ['driver', 'host', 'user', 'password', 'name'];
            foreach ($requiredKeys as $key) {
                if (!isset($dsn[$key])) {
                    throw new UnsupportedException("No {key} in connection settings keys: {keys}", ['keys' => array_keys($dsn), 'key' => $key]);
                }
                if (!empty($dsn[$key])) {
                    throw new UnsupportedException("Empty {key} in connection settings keys: {keys}", ['keys' => array_keys($dsn), 'key' => $key]);
                }
            }
            $urlParts = [
                'scheme' => $dsn['driver'],
                'user' => $dsn['user'],
                'password' => $dsn['password'],
                'host' => $dsn['host'],
                'path' => $dsn['path'] ?? '/' . $dsn['name'],
            ];
            return URL::stringify($urlParts);
        } else {
            throw new ConfigurationException([
                self::class,
                'connections',
            ], 'Bad configuration {type}', ['type' => Types::type($dsn)]);
        }
    }

    /**
     * @param mixed $dsn
     * @param string $name
     * @return EntityManager
     * @throws ConfigurationException
     * @throws Exception
     */
    private function _connectDSN(mixed $dsn, string $name): EntityManager
    {
        if (is_string($dsn)) {
            $parser = new DsnParser();

            try {
                $parts = $parser->parse($dsn);
            } catch (MalformedDsnException $e) {
                throw new ConfigurationException([
                    self::class,
                    'connections',
                    $name,
                ], 'Invalid URL {dsn}', ['dsn' => $dsn], $e);
            }
        } elseif (is_array($dsn)) {
            $parts = $dsn;
        } else {
            throw new ConfigurationException([
                self::class,
                'connections',
                $name,
            ], 'Bad configuration {type}', ['type' => Types::type($dsn)]);
        }
        $connection = DriverManager::getConnection($parts, $this->ormConfig);
        foreach (array_keys(self::$zeskTypes) as $type) {
            $connection->getDatabasePlatform()->registerDoctrineTypeMapping('db_' . $type, $type);
        }

        try {
            return new EntityManager($connection, $this->ormConfig, $this->eventManager);
        } catch (MissingMappingDriverImplementation $e) {
            throw new ConfigurationException([
                self::class,
                'connections',
                $name,
            ], 'Missing driver {parts}', ['parts' => $parts]);
        }
    }

    /**
     * Name of the default entity manager
     * @return string
     */
    private function defaultEntityManager(): string
    {
        return $this->optionString('default', 'default');
    }

    /**
     * @param string $name
     * @param bool $ensureOpen
     * @return EntityManager
     * @throws ConfigurationException
     * @throws DirectoryCreate
     * @throws DirectoryNotFound
     * @throws DirectoryPermission
     * @throws Exception
     * @throws NotFoundException
     * @throws UnsupportedException
     */
    public function entityManager(string $name = '', bool $ensureOpen = false): EntityManager
    {
        if ($name === '') {
            $name = $this->defaultEntityManager();
        }
        if (count($this->managers) === 0) {
            // Lazy setup. May not be the best idea.
            $this->configureORM();
        }
        if (array_key_exists($name, $this->managers)) {
            $em = $this->managers[$name];
            if ($ensureOpen) {
                if ($em->isOpen()) {
                    return $em;
                }
                $dsn = $this->dsnFromName($name);
                $em = $this->_connectDSN($dsn, $name);
                $this->managers[$name] = $em;
            }
            return $em;
        }

        throw new NotFoundException('No entityManager with name {name}', ['name' => $name]);
    }

    /**
     * @param array $entities
     * @param string $name
     * @return array
     * @throws ConfigurationException
     * @throws DirectoryCreate
     * @throws DirectoryNotFound
     * @throws DirectoryPermission
     * @throws Exception
     * @throws NotFoundException
     * @throws UnsupportedException
     */
    public function schemaSynchronizeSQL(array $entities, string $name = ''): array
    {
        $em = $this->entityManager($name);
        $tool = new SchemaTool($em);
        $classMetadata = [];
        foreach ($entities as $entity) {
            $classMetadata[] = $em->getClassMetadata($entity);
        }
        $sql = $tool->getUpdateSchemaSql($classMetadata);
        return $sql;
    }

    /**
     * @param array $entities
     * @param string $name
     * @return array
     * @throws ConfigurationException
     * @throws DirectoryCreate
     * @throws DirectoryNotFound
     * @throws DirectoryPermission
     * @throws Exception
     * @throws NotFoundException
     * @throws UnsupportedException
     */
    public function schemaSynchronize(array $entities, string $name = ''): array
    {
        $connection = $this->entityManager($name)->getConnection();
        $sqlList = $this->schemaSynchronizeSQL($entities, $name);
        $results = [];
        foreach ($sqlList as $sql) {
            $results[$sql] = $connection->executeQuery($sql);
        }
        return $results;
    }

    /**
     * @param string $entityName
     * @param string $name
     * @return array
     * @throws ConfigurationException
     * @throws DirectoryCreate
     * @throws DirectoryNotFound
     * @throws DirectoryPermission
     * @throws Exception
     * @throws NotFoundException
     * @throws UnsupportedException
     */
    public function dependentEntities(string $entityName, string $name = ''): array
    {
        $depend = [];
        foreach ($this->entityManager($name)->getClassMetadata($entityName)->getAssociationMappings() as $mapping) {
            if ($mapping instanceof AssociationMapping) {
                $entity = $mapping->targetEntity;
                if ($entity) {
                    $depend[$entity] = true;
                }
            } elseif (is_string($mapping)) {
                $depend[$mapping] = true;
            }
        }
        unset($depend[$entityName]);
        return array_keys($depend);
    }
}
