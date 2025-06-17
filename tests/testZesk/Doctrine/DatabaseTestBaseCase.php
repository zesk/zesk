<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 *
 * Unit tests for database tests
 */

namespace testZesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use testZesk\ApplicationTestBase;
use zesk\Doctrine\Model;
use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Types;
use zesk\Module;

/**
 *
 * @author kent
 */
class DatabaseTestBaseCase extends ApplicationTestBase
{
	public EntityManager $em;

	/**
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 */
	public function initialize(): void
	{
		parent::initialize();
		$this->testApplication->modules->load('Doctrine');
		$this->testApplication->setOption('userClass', User::class);

		$password = $_SERVER['DATABASE_ROOT_PASSWORD'] ?? null;
		if (!$password) {
			throw new ConfigurationException('DATABASE_ROOT_PASSWORD', 'Database root password is required.');
		}
		$this->testApplication->configuration->setPath([
			Module::class,
			'connections',
			'default',
		], [
			'driver' => 'mysqli',
			'user' => $_SERVER['DATABASE_ROOT_USER'] ?? 'root',
			'password' => $password,
			'host' => $_SERVER['DATABASE_HOST'] ?? 'localhost',
			'dbname' => $_SERVER['DATABASE_TEST_NAME'] ?? 'zeskTest',
		]);
		$this->em = $this->testApplication->entityManager();
	}

	/**
	 * @return Module
	 */
	final protected function doctrineModule(): Module
	{
		return $this->testApplication->doctrineModule();
	}

	/**
	 * @return ObjectRepository
	 */
	public function getRepository(string $name): ObjectRepository
	{
		return $this->em->getRepository($name);
	}

	/**
	 * @param string $entity
	 * @return string
	 */
	public function entityTable(string $entity): string
	{
		return $this->em->getClassMetadata($entity)->getTableName();
	}

	/**
	 * @return EntityManager
	 * @deprecated 2023-04
	 */
	public function getDatabase(): EntityManager
	{
		return $this->em;
	}

	//	/**
	//	 * @param string $name
	//	 * @param string $create_sql
	//	 * @return void
	//	 * @throws Duplicate
	//	 * @throws SQLException
	//	 * @throws TableNotFound
	//	 */
	//	final public function dropAndCreateTable(string $name, string $create_sql): void {
	//		$db = $this->data
	//
	//		$db->query("DROP TABLE IF EXISTS `$name`");
	//		$db->query($create_sql);
	//		if (!$this->optionBool('debug_keep_tables')) {
	//			register_shutdown_function([$db, 'query', ], "DROP TABLE IF EXISTS `$name`");
	//		}
	//	}
	//
	/**
	 * @param int $expected
	 * @param string $modelClass
	 * @return void
	 */
	final protected function assertRowCount(int $expected, string $modelClass): void
	{
		$actual = $this->em->getRepository($modelClass)->count([]);
		$this->assertEquals($expected, $actual, "$modelClass does not have expected $expected rows (has $actual)");
	}

	/**
	 */
	final protected function assertRepositoryMatch(string $modelClass, array $match = []): void
	{
		$objects = $this->em->getRepository($modelClass)->findAll();
		$rows = [];
		foreach ($objects as $item) {
			/* @var $item Model */
			$rows[] = $item->members();
		}
		$headers = null;
		$databaseRows = [];
		foreach ($match as $row) {
			if (!$headers) {
				$headers = $row;
			} else {
				$mapped_row = [];
				foreach ($headers as $k => $label) {
					if ($label[0] === '-') {
						continue;
					}
					$mapped_row[$label] = $row[$k];
				}
				$databaseRows[] = $mapped_row;
			}
		}
		$this->assertEquals($rows, $databaseRows, "Matching $modelClass to row values");
	}

	/**
	 * Synchronize the given classes with the database schema
	 *
	 * @return array
	 */
	public function schemaSynchronize(string|array $entities): array
	{
		$doctrine = $this->doctrineModule();
		$entities = Types::toList($entities);
		if (count($entities) === 0) {
			return [];
		}
		$collection = array_flip($entities);
		foreach ($entities as $entity) {
			$collection += array_flip($doctrine->dependentEntities($entity));
		}
		return $this->testApplication->doctrineModule()->schemaSynchronize(array_keys($collection));
	}

	final protected function sqlNormalizeTrim(string $sql): string
	{
		return preg_replace('/\s+/', ' ', trim($sql));
	}

	/**
	 * Compare SQL loosely
	 *
	 * @param string $expected
	 * @param string $sql
	 * @param string $message
	 * @return void
	 */
	final protected function assertSQLEquals(string $expected, string $sql, string $message = ''): void
	{
		$this->assertEquals($this->sqlNormalizeTrim($expected), $this->sqlNormalizeTrim($sql), $message);
	}
}
