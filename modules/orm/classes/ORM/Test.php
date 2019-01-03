<?php
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
	public function hook_construct() {
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
	public function schema_synchronize($classes, array $options = array()) {
		$app = $this->application;
		$results = array();
		foreach (to_list($classes) as $class) {
			$class_object = $this->application->class_orm_registry($class);
			$db = $class_object->database();
			$results[$class] = $db->query($this->application->orm_registry()->schema_synchronize($db, array(
				$class,
			), $options + array(
				"follow" => true,
			)));
		}
		return $results;
	}
}
