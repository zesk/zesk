<?php
namespace zesk;

class Classes {
	/**
	 *
	 * @var integer
	 */
	const VERSION = 5;

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
	protected $class_case = array();

	/**
	 * Registry of class names
	 *
	 * @var array
	 */
	protected $classes = array();

	/**
	 * @var array
	 */
	protected $subclasses = array();

	/**
	 * @var array
	 */
	protected $hierarchy = array();

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 *
	 */
	public function __construct(Kernel $zesk) {
		$this->initialize($zesk);
	}

	/**
	 * Register hooks
	 *
	 * @param Kernel $kernel
	 */
	public function initialize(Kernel $kernel) {
		$kernel->hooks->add("exit", array(
			$this,
			"on_exit",
		), array(
			"arguments" => array(
				$kernel,
			),
		));
	}

	/**
	 * @param Kernel $zesk
	 * @return \zesk\Classes
	 */
	public static function instance(Kernel $zesk) {
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
	 *
	 */
	public function on_exit(Kernel $kernel) {
		if ($this->dirty) {
			$this->dirty = false;
			$kernel->cache->saveDeferred($kernel->cache->getItem(__CLASS__)->set($this));
		}
	}

	/**
	 *
	 * @param string $class
	 */
	private function _add($class) {
		$this->class_case[strtolower($class)] = $class;
		$parent_classes = $this->hierarchy($class);
		$this->classes[$class] = array();
		$this->subclasses[$class] = array();
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
	 * @return
	 */
	public function register($class = null) {
		if (is_array($class)) {
			$result = array();
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
		$class = isset($this->class_case[$lowclass]) ? $this->class_case[$lowclass] : $class;
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
		$classes = array(
			$class,
		);
		$result = array();
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
			$result = array();
			foreach ($mixed as $key) {
				$result[$key] = $this->hierarchy($key, $stop_class);
			}
			return $result;
		}
		if (array_key_exists($mixed, $this->hierarchy)) {
			$result = $this->hierarchy[$mixed];
		} else {
			$parent = get_parent_class($mixed);
			$result = array(
				$mixed,
			);
			if ($parent !== false) {
				$result = array_merge($result, $this->hierarchy($parent));
			}
			$this->hierarchy[$mixed] = $result;
			$this->dirty = true;
		}
		if ($stop_class === null) {
			return $result;
		}
		$stop_result = array();
		foreach ($result as $class) {
			$stop_result[] = $class;
			if ($class === $stop_class) {
				return $stop_result;
			}
		}
		return $stop_result;
	}
}
