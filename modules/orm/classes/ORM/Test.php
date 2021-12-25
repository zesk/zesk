<?php declare(strict_types=1);
namespace zesk\ORM;

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
		if (!in_array("ORM", $this->load_modules)) {
			$this->load_modules[] = "ORM";
		}
	}

	/**
	 * Synchronize the given classes with the database schema
	 *
	 * @param list|string $classes
	 * @param array $options
	 * @return array[classname]
	 */
	public function schema_synchronize($classes, array $options = []) {
		$app = $this->application;
		$results = [];
		foreach (to_list($classes) as $class) {
			$class_object = $this->application->class_orm_registry($class);
			$db = $class_object->database();
			$results[$class] = $db->query($app->orm_module()->schema_synchronize($db, [
				$class,
			], $options + [
				"follow" => true,
			]));
		}
		return $results;
	}
}
