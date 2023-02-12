<?php
declare(strict_types=1);
/**
 * Unit tests for database tests
 */

namespace zesk;

/**
 *
 * @author kent
 */
class DatabaseUnitTest extends UnitTest {
	/**
	 * @return Database
	 */
	public function getDatabase(): Database {
		return $this->application->databaseRegistry();
	}

	/**
	 * @not_test
	 *
	 * @param string $name
	 * @param array|string|null $extra_cols
	 * @param bool $uniq
	 * @return string
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	final public function prepareTestTable(string $name, array|string|null $extra_cols = null, bool $uniq = true): string {
		$cols = [];
		$cols[] = 'id int(11) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT';
		$cols[] = 'foo int(11) NOT NULL';
		if (is_string($extra_cols) && !empty($extra_cols)) {
			$cols[] = $extra_cols;
		} elseif (is_array($extra_cols)) {
			$cols = array_merge($cols, $extra_cols);
		}
		if ($uniq) {
			$cols[] = 'UNIQUE `f` (`foo`)';
		}
		$cols = implode(', ', $cols);
		$create_sql = "CREATE TABLE `$name` ( $cols )";
		$this->dropAndCreateTable($name, $create_sql);
		return $name;
	}

	/**
	 * @param string $name
	 * @param string $create_sql
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	final public function dropAndCreateTable(string $name, string $create_sql): void {
		$db = $this->application->databaseRegistry();
		$db->query("DROP TABLE IF EXISTS `$name`");
		$db->query($create_sql);
		if (!$this->optionBool('debug_keep_tables')) {
			register_shutdown_function([$db, 'query', ], "DROP TABLE IF EXISTS `$name`");
		}
	}

	/**
	 * @param int $expected
	 * @param string|object $ormClass
	 * @return void
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 */
	final protected function assertRowCount(int $expected, string|object $ormClass): void {
		$ormObject = $this->application->ormRegistry($ormClass);
		$idName = $ormObject->idColumn();
		$actual = $ormObject->querySelect()->addWhat('total', "COUNT($idName)")->integer();
		$className = $ormObject::class;
		$this->assertEquals($expected, $actual, "$className does not have expected $expected rows (has $actual)");
	}

	/**
	 *
	 * @param string $table
	 * @param array $match
	 */
	final protected function assertTableMatch(string $table, array $match = []): void {
		$database = $this->application->databaseRegistry();
		$headers = null;
		$header_row = null;
		$databaseRows = [];
		foreach ($match as $row) {
			if (!$headers) {
				$headers = $row;
				$header_row = $row;
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
		$headers = [];
		foreach ($header_row as $header) {
			if ($header[0] === '-') {
				continue;
			}
			$headers[] = $header;
		}
		$rows = $database->queryArray('SELECT ' . implode(',', $headers) . " FROM $table");
		$this->assertEquals($rows, $databaseRows, "Matching $table to row values");
	}

	/**
	 * Synchronize the given classes with the database schema
	 *
	 * @param array|string $classes
	 * @param array $options
	 * @return array[classname]
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function schemaSynchronize(array|string $classes, array $options = []): array {
		$app = $this->application;
		$results = [];
		foreach (toList($classes) as $class) {
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
