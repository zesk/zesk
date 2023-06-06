<?php
declare(strict_types=1);

namespace zesk\Application;

use TypeError;

class Classes {
	/**
	 *
	 * @var integer
	 */
	public const VERSION = 5;

	/**
	 * Version number of serialized file
	 *
	 * @var integer
	 */
	protected int $version = self::VERSION;

	/**
	 * Lowercase class name -> capitalized class name
	 *
	 * @var array
	 */
	protected array $class_case = [];

	/**
	 * Registry of class names
	 *
	 * @var array
	 */
	protected array $classes = [];

	/**
	 * @var array
	 */
	protected array $subclasses = [];

	/**
	 * @var array
	 */
	protected array $hierarchy = [];

	/**
	 * @var boolean
	 */
	protected bool $dirty = false;

	/**
	 * Classes constructor.
	 */
	public function __construct() {
		$this->loadDeclared();
	}

	/**
	 * @return void
	 */
	private function loadDeclared(): void {
		foreach (get_declared_classes() as $class) {
			$this->register($class);
		}
	}

	/**
	 *
	 * @param string $class
	 */
	private function _add(string $class): void {
		$this->class_case[strtolower($class)] = $class;
		$parent_classes = $this->hierarchy($class);
		$this->classes[$class] = [];
		$this->subclasses[$class] = [];
		$this->dirty = true;
		array_shift($parent_classes);
		foreach ($parent_classes as $parent_class) {
			$this->classes[$parent_class][$class] = 1;
			$this->subclasses[$parent_class] = array_keys($this->classes[$parent_class]);
			$class = $parent_class;
		}
	}

	/**
	 * Register a global hook by class - returns same type passed.
	 *
	 * @param string|array $class
	 * @return string|array
	 */
	public function register(string|array $class): string|array {
		if (is_array($class)) {
			$result = [];
			foreach ($class as $classy) {
				$result[$classy] = $this->register($classy);
			}
			return $result;
		}
		if (empty($class)) {
			// Do we need to warn? Not sure if silent failure is best. Probably for now.
			return '';
		}
		$lowercase_class = strtolower($class);
		$class = $this->class_case[$lowercase_class] ?? $class;
		if (!array_key_exists($class, $this->classes)) {
			$this->_add($class);
		}
		return $this->subclasses[$class];
	}

	/**
	 * Return all known subclasses of a class including grandchildren and great-grandchildren, etc.
	 *
	 * @param string $class
	 * @return string[]
	 */
	public function subclasses(string $class): array {
		$classes = [
			$class,
		];
		$result = [];
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (empty($class)) {
				continue;
			}
			$result[] = $class;
			$subclasses = $this->register($class);
			if (is_array($subclasses)) {
				$classes = array_merge($classes, $subclasses);
			}
		}
		return $result;
	}

	/**
	 * Retrieve a class hierarchy from leaf to base
	 *
	 * @param object|string $mixed An object or string to find class hierarchy for
	 * @param string $stop_class Return up to and including this class, blank to include all classes.
	 * @return array
	 */
	public function hierarchy(object|string $mixed, string $stop_class = ''): array {
		if (!is_string($mixed)) {
			$mixed = $mixed::class;
		}
		if (array_key_exists($mixed, $this->hierarchy)) {
			$result = $this->hierarchy[$mixed];
		} else {
			try {
				$parent = get_parent_class($mixed);
			} catch (TypeError $t) {
				throw new TypeError("$mixed: " . $t->getMessage(), $t->getCode(), $t);
			}
			$result = [
				$mixed,
			];
			if ($parent !== false) {
				$result = array_merge($result, $this->hierarchy($parent));
			}
			$this->hierarchy[$mixed] = $result;
			$this->dirty = true;
		}
		if ($stop_class === '') {
			return $result;
		}
		$stop_result = [];
		foreach ($result as $class) {
			$stop_result[] = $class;
			if ($class === $stop_class) {
				return $stop_result;
			}
		}
		return $stop_result;
	}
}
