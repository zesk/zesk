<?php declare(strict_types=1);
namespace zesk;

use Psr\Cache\InvalidArgumentException;

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
	protected $version = self::VERSION;

	/**
	 * Lowercase class name -> capitalized class name
	 *
	 * @var array
	 */
	protected $class_case = [];

	/**
	 * Registry of class names
	 *
	 * @var array
	 */
	protected $classes = [];

	/**
	 * @var array
	 */
	protected $subclasses = [];

	/**
	 * @var array
	 */
	protected $hierarchy = [];

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * Classes constructor.
	 * @param Kernel $zesk
	 * @throws Exception_Semantics
	 */
	public function __construct(Kernel $zesk) {
		$this->initialize($zesk);
	}

	/**
	 * Register hooks
	 * @param Kernel $kernel
	 * @throws Exception_Semantics
	 */
	public function initialize(Kernel $kernel): void {
		$kernel->hooks->add('exit', [
			$this,
			'on_exit',
		], [
			'arguments' => [
				$kernel,
			],
		]);
	}

	/**
	 * @param Kernel $zesk
	 * @return self
	 * @throws Exception_Semantics
	 * @throws InvalidArgumentException
	 */
	public static function instance(Kernel $zesk): self {
		$cache_item = $zesk->cache->getItem(__CLASS__);
		if ($cache_item->isHit()) {
			$classes = $cache_item->get();
			if ($classes instanceof self && $classes->version === self::VERSION) {
				$classes->initialize($zesk);
			} else {
				$classes = new self($zesk);
			}
		} else {
			$classes = new self($zesk);
		}
		return $classes;
	}

	/**
	 * @param Kernel $kernel
	 * @throws InvalidArgumentException
	 */
	public function on_exit(Kernel $kernel): void {
		if ($this->dirty) {
			$this->dirty = false;
			$kernel->cache->saveDeferred($kernel->cache->getItem(__CLASS__)->set($this));
		}
	}

	/**
	 *
	 * @param string $class
	 */
	private function _add($class): void {
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
	 * Register a global hook by class
	 *
	 * @return array
	 */
	public function register($class = null) {
		if (is_array($class)) {
			$result = [];
			foreach ($class as $classy) {
				$result[$classy] = $this->register($classy);
			}
			return $result;
		}
		if ($class === null) {
			return $this->classes;
		}
		if (empty($class)) {
			// Do we need to warn? Not sure if silent failure is best. Probably for now.
			return null;
		}
		$lowclass = strtolower($class);
		$class = $this->class_case[$lowclass] ?? $class;
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
	public function subclasses($class) {
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
	 * @param mixed $mixed
	 *        	An object or string to find class hierarchy for
	 * @param string $stop_class
	 *        	Return up to and including this class
	 * @return array
	 */
	public function hierarchy($mixed, $stop_class = null) {
		if ($mixed === null) {
			return $this->hierarchy;
		}
		if (is_object($mixed)) {
			$mixed = get_class($mixed);
		} elseif (is_array($mixed)) {
			$result = [];
			foreach ($mixed as $key) {
				$result[$key] = $this->hierarchy($key, $stop_class);
			}
			return $result;
		}
		if (array_key_exists($mixed, $this->hierarchy)) {
			$result = $this->hierarchy[$mixed];
		} else {
			$parent = get_parent_class($mixed);
			$result = [
				$mixed,
			];
			if ($parent !== false) {
				$result = array_merge($result, $this->hierarchy($parent));
			}
			$this->hierarchy[$mixed] = $result;
			$this->dirty = true;
		}
		if ($stop_class === null) {
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
