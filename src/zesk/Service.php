<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\ClassNotFound;

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
		return in_array($type, $types);
	}

	/**
	 * Valid service types - taken from the class hierarchy
	 * @param Application $application
	 * @return array
	 */
	public static function validTypes(Application $application): array {
		$types = $application->classes->register(self::class);
		return ArrayTools::valuesRemovePrefix($types, [self::class . '_', self::class . '\\']);
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
			return $application->classes->register(self::class);
		}
		return [];
	}

	/**
	 * Subclasses should create a factory_$type function to allow parameters to be passed
	 *
	 * @param Application $application
	 * @param string $type
	 * @return self
	 * @throws ClassNotFound
	 * @throws Semantics
	 */
	public static function factory(Application $application, string $type, array $arguments = []): self {
		if (!self::validType($application, $type)) {
			throw new Semantics('Invalid service type {type}', [
				'type' => $type,
			]);
		}
		array_unshift($arguments, $application);
		$class = $application->configuration->getPath([self::class, $type]);
		if (!$class) {
			$classes = self::serviceClasses($application, $type);
			if (count($classes) === 0) {
				throw new Semantics('No service classes for {type}', [
					'type' => $type,
				]);
			}
			$class = array_shift($classes);
		}
		assert(class_exists($class, false));
		$result = $application->objects->factory($class, $arguments);
		assert($result instanceof self);
		return $result;
	}
}
