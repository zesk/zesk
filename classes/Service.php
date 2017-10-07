<?php
namespace zesk;

class Service extends Hookable {
	
	/**
	 * 
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * 
	 * @var unknown
	 */
	private static $valid_types = null;
	
	/**
	 * 
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options($application);
	}
	/**
	 * Valid service type
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function valid_type(Application $application, $type = null) {
		if (self::$valid_types === null) {
			$service_class = __NAMESPACE__ . "\\Service";
			$types = arr::change_value_case($application->classes->register($service_class));
			$types = arr::unprefix($types, strtolower($service_class) . "_");
			self::$valid_types = arr::flip_copy($types);
		}
		return $type === null ? self::$valid_types : array_key_exists(strtolower($type), self::$valid_types);
	}
	
	/**
	 * Retrieve the classes which implement the selected service
	 *
	 * @param string $type
	 * @return array:string
	 */
	public static function service_classes(Application $application, $type) {
		if (self::valid_type($application, $type)) {
			return $application->classes->register(__NAMESPACE__ . "\\Service_" . $type);
		}
		return array();
	}
	
	/**
	 * Subclasses should create a factory_$type function to allow parameters to be passed
	 *
	 * @param string $type
	 * @throws \Exception_Semantics
	 * @return self
	 */
	public static function factory(Application $application, $type) {
		if (!self::valid_type($application, $type)) {
			throw new Exception_Semantics("Invalid service type {type}", array(
				"type" => $type
			));
		}
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		array_unshift($args, $application);
		$class = $application->configuration->path_get(__NAMESPACE__ . "\\Service_$type::class");
		if (!$class) {
			$classes = self::service_classes($application, $type);
			if (count($classes) === 0) {
				throw new Exception_Semantics("No service classes for {type}", array(
					"type" => $type
				));
			}
			$class = array_shift($classes);
		}
		return $application->objects->factory($class, $args);
	}
}
