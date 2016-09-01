<?php

namespace zesk;

class Service extends \Hookable {
	private static $valid_types = null;
	
	/**
	 * Valid service type
	 *
	 * @param string $type        	
	 * @return bool
	 */
	public static function valid_type($type = null) {
		if (self::$valid_types === null) {
			$service_class = __NAMESPACE__ . "\\Service";
			$types = \arr::change_value_case(\zesk\Classes::register($service_class));
			$types = \arr::unprefix($types, strtolower($service_class) . "_");
			self::$valid_types = \arr::flip_copy($types);
		}
		return $type === null ? self::$valid_types : array_key_exists(strtolower($type), self::$valid_types);
	}
	
	/**
	 * Retrieve the classes which implement the selected service
	 *
	 * @param string $type        	
	 * @return array:string
	 */
	public static function service_classes($type) {
		if (self::valid_type($type)) {
			return \zesk\Classes::register(__NAMESPACE__ . "\\Service_" . $type);
		}
		return array();
	}
	
	/**
	 * Subclasses should create a factory_$type function to allow parameters to be passed
	 *
	 * @param string $type        	
	 * @throws \Exception_Semantics
	 */
	public static function factory($type) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (!self::valid_type($type)) {
			throw new \Exception_Semantics("Invalid service type {type}", array(
				"type" => $type
			));
		}
		$args = func_get_args();
		array_shift($args);
		$class = $zesk->path_get("Service_$type::class");
		if (!$class) {
			$classes = self::service_classes($type);
			if (count($classes) === 0) {
				throw new \Exception_Semantics("No service classes for {type}", array(
					"type" => $type
				));
			}
			$class = array_shift($classes);
		}
		return \zesk::factory_array($class, $args);
	}
}