<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;

/**
 *
 * @author kent
 *
 */
class Test extends \zesk\Test {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Test::initialize()
	 */
	public function hook_construct(): void {
		if (!in_array('ORM', $this->load_modules)) {
			$this->load_modules[] = 'ORM';
		}
	}

	/**
	 * Synchronize the given classes with the database schema
	 *
	 * @param array|string $classes
	 * @param array $options
	 * @return array
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function schema_synchronize(array|string $classes, array $options = []): array {
		$app = $this->application;
		$results = [];
		foreach (toList($classes) as $class) {
			$class_object = $this->application->class_ormRegistry($class);
			$db = $class_object->database();
			$results[$class] = $db->queries($app->orm_module()->schema_synchronize($db, [
				$class,
			], $options + [
				'follow' => true,
			]));
		}
		return $results;
	}
}
