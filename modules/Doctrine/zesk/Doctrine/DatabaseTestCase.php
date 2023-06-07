<?php
declare(strict_types=1);
/**
 * Unit tests for database tests
 */

namespace zesk\Doctrine;

use Doctrine\Persistence\ObjectRepository;
use zesk\ArrayTools;
use zesk\PHPUnit\TestCase;

use Doctrine\ORM\EntityManager;
use zesk\Types;

/**
 *
 * @author kent
 */
class DatabaseTestCase extends TestCase {
	public EntityManager $em;

	public function initialize(): void {
		$this->application->modules->load('Doctrine');
		$this->application->setOption('userClass', User::class);
		$this->em = $this->application->entityManager();
	}

	/**
	 * @return Module
	 */
	final protected function doctrineModule(): Module {
		return $this->application->doctrineModule();
	}

	/**
	 * @return ObjectRepository
	 */
	public function getRepository(string $name): ObjectRepository {
		return $this->em->getRepository($name);
	}

	/**
	 * @param string $entity
	 * @return string
	 */
	public function entityTable(string $entity): string {
		return $this->em->getClassMetadata($entity)->getTableName();
	}
	/**
	 * @return EntityManager
	 * @deprecated 2023-04
	 */
	public function getDatabase(): EntityManager {
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
	final protected function assertRowCount(int $expected, string $modelClass): void {
		$actual = $this->em->getRepository($modelClass)->count([]);
		$this->assertEquals($expected, $actual, "$modelClass does not have expected $expected rows (has $actual)");
	}

	/**
	 */
	final protected function assertRepositoryMatch(string $modelClass, array $match = []): void {
		$objects = $this->em->getRepository($modelClass)->findAll();
		$rows = [];
		foreach ($objects as $item) {
			/* @var $item Model */
			$rows[] = $item->variables();
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
	public function schemaSynchronize(string|array $entities): array {
		$doctrine = $this->doctrineModule();
		$entities = Types::toList($entities);
		$collection = array_flip($entities);
		foreach ($entities as $entity) {
			$collection += array_flip($doctrine->dependentEntities($entity));
		}
		return $this->application->doctrineModule()->schemaSynchronize(array_keys($collection));
	}

	final protected function sqlNormalizeTrim(string $sql): string {
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
	final protected function assertSQLEquals(string $expected, string $sql, string $message = ''): void {
		$this->assertEquals($this->sqlNormalizeTrim($expected), $this->sqlNormalizeTrim($sql), $message);
	}
}
