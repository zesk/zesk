<?php declare(strict_types=1);
/**
 *
 */
use zesk\ArrayTools;
use zesk\PHP;
use zesk\Exception_Parameter;
use zesk\Model;
use zesk\Application;
use zesk\Exception_Class_NotFound;

/**
 *
 * @author kent
 *
 */
class Workflow extends Model {
	/**
	 * Array of classes for steps
	 *
	 * @var array
	 */
	protected $steps = [];

	/**
	 *
	 * @var array
	 */
	protected $variables = [];

	/**
	 * Computed from steps
	 *
	 * @var array of Workflow_Step
	 */
	protected $sorted_steps = null;

	/**
	 *
	 * @var boolean[string]
	 */
	protected $step_status = null;

	/**
	 *
	 * @var boolean
	 */
	public $completed = true;

	/**
	 *
	 * @var integer
	 */
	public $steps_completed = 0;

	/**
	 *
	 * @var integer
	 */
	public $steps_total = 0;

	/**
	 * Whether the above are computed or not
	 *
	 * @var unknown
	 */
	private $dirty = true;

	/**
	 *
	 * @param array $variables
	 * @param array $options
	 */
	public function __construct(Application $application, array $variables = [], array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->variable($variables);
		$this->_init_steps();
		$this->_clean_completed();
		$this->initialize();
	}

	final public function percent() {
		return 100 * ($this->steps_total === 0 ? 1 : $this->steps_completed / $this->steps_total);
	}

	protected function initialize(): void {
	}

	public function hash() {
		foreach ($this->sorted_steps as $id => $step) {
			$keys[] = $id;
			$keys[] = $step->hash();
		}
		return md5(implode(';', $keys));
	}

	/**
	 *
	 * @param boolean $force
	 * @return Workflow
	 */
	public function clean($force = false) {
		if ($force) {
			$this->dirty = true;
		}
		$this->_clean_completed();
		return $this;
	}

	/**
	 * Clean the steps
	 */
	private function _init_steps(): void {
		$app = $this->application;
		$this->sorted_steps = [];
		$steps = [];
		$dependencies = [];
		/**
		 * Instantiate our steps, and determine dependencies for ordering
		 */
		foreach ($this->steps as $id => $spec) {
			if (is_string($spec)) {
				$class = $spec;
				$options = [];
			} elseif (is_array($spec)) {
				[$class, $options] = $spec;
			} else {
				$app->logger->error('Workflow::_init_steps Invalid value type {type} {dump}', [
					'type' => type($spec),
					'dump' => _dump($spec),
				]);
				continue;
			}

			try {
				/* @var $step Workflow_Step */
				$steps[$id] = $step = $app->objects->factory($class, $this, $options);
			} catch (Exception_Class_NotFound $e) {
				$app->logger->error('Workflow::_init_steps Class not found {class}', [
					'class' => $step,
				]);
			}
		}
		$step_ids = array_keys($steps);
		foreach ($steps as $id => $step) {
			/*
			 * If we have no dependencies, or all of our dependencies are already in our list, then add it.
			 */
			$dep_ids = $step->dependencies();
			$dep_ids = ArrayTools::filter($dep_ids, $step_ids);
			if (count($dep_ids) === 0 || ArrayTools::has($this->sorted_steps, $dep_ids)) {
				$this->sorted_steps[$id] = $step;
			} else {
				$dependencies[$id] = $dep_ids;
			}
		}
		while (count($dependencies) !== 0) {
			$changed = false;
			foreach ($dependencies as $id => $dep_ids) {
				if (ArrayTools::has($this->sorted_steps, $dep_ids)) {
					$this->sorted_steps[$id] = $steps[$id];
					unset($dependencies[$id]);
					$changed = true;
				}
			}
			if (!$changed) {
				$this->application->logger->error('Unable to resolve dependencies in {class}: {deps}', [
					'class' => get_class($this),
					'deps' => array_keys($dependencies),
				]);
				break;
			}
		}
	}

	private function _clean_completed(): void {
		if (!$this->dirty) {
			return;
		}
		$this->call_hook('clean');
		$this->steps_total = 0;
		$this->steps_completed = 0;
		/* @var $step Workflow_Step */
		foreach ($this->sorted_steps as $step) {
			$step->refresh($this);
			$this->step_status[get_class($step)] = $step->is_completed();
			$this->steps_total += $step->substeps_total();
			$this->steps_completed += $step->substeps_completed();
		}
		$this->completed = ($this->steps_completed === $this->steps_total);
		$this->dirty = false;
		$this->call_hook($this->completed ? 'completed' : 'incomplete');
	}

	/**
	 * Return variables
	 *
	 * @return array
	 */
	public function variables(): array {
		return $this->variables;
	}

	/**
	 * Get/set a variable for this workflow
	 *
	 * @param string $name
	 * @param string $value
	 * @return array|mixed|Workflow
	 */
	public function variable($name = null, $value = null) {
		if ($name === null) {
			return $this->variables;
		}
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->variable($k, $v);
			}
			return $this;
		}
		if (is_string($name) || is_numeric($name)) {
			if ($value === null) {
				return avalue($this->variables, $name);
			}
			$this->dirty = true;
			$this->variables[$name] = $value;
			return $this;
		}

		throw new Exception_Parameter('{class}::variable invalid name type {type} {dump}', [
			'class' => get_class($this),
			'type' => type($name),
			'dump' => _dump($name),
		]);
	}

	public function render($theme_name = null, $variables = null, $default = '') {
		if ($theme_name === null) {
			$theme_name = 'steps';
		}
		$this->_clean_completed();
		$themes = ArrayTools::suffixValues($this->application->classes->hierarchy(get_class($this), __CLASS__), "/$theme_name");
		return $this->application->theme($themes, [
			'workflow' => $this,
			'completed' => $this->completed,
			'steps' => $this->sorted_steps,
			'steps_completed' => $this->steps_completed,
			'steps_total' => $this->steps_total,
		] + to_array($variables, []), [
			'first' => true,
			'default' => $default,
		]);
	}

	public function __toString() {
		return PHP::dump($this->variables);
	}

	/*
	 * Only place to access ->$name is here
	 */
	public function __get($name) {
		return array_key_exists($name, $this->variables) ? $this->variables[$name] : null;
	}

	public function __set($key, $value): void {
		$this->variables[$key] = $value;
		$this->_inited = true;
	}

	public function __unset($key): void {
		unset($this->variables[$key]);
	}

	public function __isset($name) {
		return isset($this->variables[$name]);
	}
}
