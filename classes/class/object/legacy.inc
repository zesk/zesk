<?php

/**
 * Backwards compatible class to support objects which use members to do specification. These are copied into the
 * Class_Object definition to behave identically.
 * 
 * @author kent
 */
class Class_Object_Legacy extends Class_Object {
	/**
	 * Array of class propreties to copy over
	 *
	 * @var array
	 */
	static $class_properties = null;
	
	/**
	 * Create a legacy instance of a class for those which use the in-object method
	 *
	 * @param Object $object        	
	 */
	public function __construct(Object $object) {
		if (!is_array(self::$class_properties)) {
			$refl = new ReflectionClass("Class_Object");
			self::$class_properties = array();
			foreach ($refl->getProperties() as $property) {
				$name = $property->name;
				if (!in_array($name, array(
					'class',
					'database_name',
					'database'
				))) {
					self::$class_properties[] = $property->name;
				}
			}
		}
		$refl = new ReflectionObject($object);
		foreach (self::$class_properties as $property) {
			if ($refl->hasProperty($property)) {
				$prop = $refl->getProperty($property);
				if ($prop->isProtected()) {
					$prop->setAccessible(true);
					$this->$property = $prop->getValue($object);
				}
			}
		}
		parent::__construct($object);
	}
}

