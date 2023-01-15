<?php declare(strict_types=1);
namespace zesk;

class Service extends Hookable {
	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
	}

	/**
	 * Valid service type
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function validType(Application $application, string $type): bool {
		$types = self::validTypes($application);
		return array_key_exists($type, $types);
	}

	/**
	 * Valid service types - taken from the class hierarchy
	 * @param Application $application
	 * @return array
	 */
	public static function validTypes(Application $application): array {
		$types = ArrayTools::changeValueCase($application->classes->register(self::class));
		$types = ArrayTools::valuesRemovePrefix($types, self::class . '_');
		return ArrayTools::valuesFlipCopy($types);
	}

	/**
	 * Retrieve the classes which implement the selected service
	 *
	 * @param Application $application
	 * @param string $type Exact class suffix 'Translate'
	 * @return array
	 */
	public static function serviceClasses(Application $application, string $type): array {
		if (self::validType($application, $type)) {
			return $application->classes->register(self::class . '_' . $type);
		}
		return [];
	}

	/**
	 * Subclasses should create a factory_$type function to allow parameters to be passed
	 *
	 * @param Application $application
	 * @param string $type
	 * @return self
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Semantics
	 */
	public static function factory(Application $application, string $type): self {
		if (!self::validType($application, $type)) {
			throw new Exception_Semantics('Invalid service type {type}', [
				'type' => $type,
			]);
		}
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		array_unshift($args, $application);
		$class = $application->configuration->getPath(__NAMESPACE__ . "\\Service_$type::class");
		if (!$class) {
			$classes = self::serviceClasses($application, $type);
			if (count($classes) === 0) {
				throw new Exception_Semantics('No service classes for {type}', [
					'type' => $type,
				]);
			}
			$class = array_shift($classes);
		}
		assert(class_exists($class, false));
		return $application->objects->factory($class, $args);
	}
}
