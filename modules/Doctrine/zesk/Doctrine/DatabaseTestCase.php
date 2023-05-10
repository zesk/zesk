<?php
declare(strict_types=1);
/**
 * Unit tests for database tests
 */

namespace zesk\Database;

use Doctrine\ORM\EntityManager;
use zesk\Doctrine\Model;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Types as BaseTypes;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\KeyNotFound;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 */
class DatabaseUnitTest extends TestCase {
	public EntityManager $em;

	public function initialize(): void {
		$this->em = $this->application->entityManager();

	}

	/**
	 * @deprecated 2023-04
	 * @return EntityManager
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
	 * @param string|object $modelClass
	 * @return void
	 * @throws Duplicate
	 * @throws KeyNotFound
	 * @throws NoResults
	 * @throws TableNotFound
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
	 * @param array|string $classes
	 * @param array $options
	 * @return array[classname]
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws SchemaException
	 * @throws TableNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws Semantics
	 * @throws SyntaxException
	 */
	public function schemaSynchronize(array|string $classes, array $options = []): array {
		$app = $this->application;
		$results = [];
		foreach (BaseTypes::toList($classes) as $class) {
			$class_object = $app->class_ormRegistry($class);
			$db = $class_object->database();
			$results[$class] = $db->queries($app->ormModule()->schemaSynchronize($db, [$class, ], $options + ['follow' => true, ]));
		}
		return $results;
	}

	final protected function sqlNormalizeTrim(string $sql): string {
		return preg_replace('/\s+/', ' ', trim($sql));
	}

	final protected function assertSQLEquals(string $expected, string $sql, string $message = ''): void {
		$this->assertEquals($this->sqlNormalizeTrim($expected), $this->sqlNormalizeTrim($sql), $message);
	}
}
