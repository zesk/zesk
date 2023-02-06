<?php declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
class Model_Settings extends Model {
	protected $_changed = [];

	protected $_accessor = [];

	protected $_access_cache = [];

	protected $ignore_variables = [];

	/**
	 * Array of key => default
	 *
	 * @var array $variables
	 */
	protected $variables = [];

	/**
	 *
	 * @var Configuration
	 */
	protected $configuration = null;

	/**
	 *
	 * @var array
	 */
	protected $state = [];

	/**
	 *
	 * {@inheritDoc}
	 */
	public function hook_construct(): void {
		$this->configuration = $this->application->configuration;
		$this->inheritConfiguration();
	}

	/**
	 *
	 * @param mixed $mixed
	 */
	public function ignore_variable($mixed = null) {
		if ($mixed === null) {
			return $this->ignore_variables;
		}
		$mixed = to_list($mixed);
		foreach ($mixed as $item) {
			if (!in_array($item, $this->ignore_variables)) {
				$this->ignore_variables[] = $item;
			}
		}
		return $this;
	}

	/**
	 *
	 * @param mixed $mixed
	 */
	public function allow_variable($mixed = null) {
		if ($mixed === null) {
			return $this->variables;
		}
		$mixed = to_list($mixed);
		foreach ($mixed as $item) {
			if (!in_array($item, $this->variables)) {
				$this->variables[$item] = null;
				if ($this->optionBool('debug_variables')) {
					$this->application->logger->debug('Adding permitted {variable} to {class}', [
						'variable' => $item,
						'class' => get_class($this),
					]);
				}
			}
		}
		return $this;
	}

	public function __isset($key) {
		if (array_key_exists($key, $this->_changed)) {
			return true;
		}
		if (array_key_exists($key, $this->variables)) {
			return $this->application->configuration->path_exists($key, false);
		}
		return isset($this->state[$key]);
	}

	private function _ignore_variable($key) {
		return in_array($key, $this->ignore_variables);
	}

	protected function _internal_set($key, $value) {
		$old = $this->_internal_get($key);
		if ($value === $old) {
			return;
		}
		// Value has definitely changed
		if (!$this->_ignore_variable($key)) {
			if ($this->optionBool('debug_changes')) {
				$this->application->logger->debug('{method} new value for key {key} {new_value} (old value was {old_value})', [
					'key' => $key,
					'old_value' => $old,
					'new_value' => $value,
					'method' => __METHOD__,
				]);
			}
			$this->_changed[$key] = $value;
		} elseif (array_key_exists($key, $this->variables)) {
			$this->configuration->setPath($key, $value);
		} else {
			if ($this->optionBool('debug_variables')) {
				$this->application->logger->warning('{method} STATE ONLY value for key {key} {new_value} (old value was {old_value})', [
					'key' => $key,
					'old_value' => $old,
					'new_value' => $value,
					'method' => __METHOD__,
				]);
			}
			$this->state[$key] = $value;
		}
		return $this;
	}

	/**
	 * Get a value from this model
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected function _internal_get($key) {
		if (array_key_exists($key, $this->_changed)) {
			return $this->_changed[$key];
		}
		if (array_key_exists($key, $this->variables)) {
			$result = $this->configuration->getPath($key);
			return $result instanceof Configuration ? $result->toArray() : $result;
		}
		$this->application->logger->debug('{variable} not permitted in {class}, using local state instead', [
			'variable' => $key,
			'class' => get_class($this),
		]);
		return $this->state[$key] ?? null;
	}

	public function variables(): array {
		$result = $this->configuration->paths_get($this->variables);
		foreach ($result as $k => $v) {
			if ($v instanceof Configuration) {
				$result[$k] = toArray($v->toArray());
			}
		}
		return $result;
	}

	public function __get($key) {
		if (array_key_exists($key, $this->_accessor)) {
			return call_user_func([
				$this,
				$this->_accessor[$key],
			]);
		}
		return $this->_internal_get($key);
	}

	public function __set($key, $value) {
		if (array_key_exists($key, $this->_accessor)) {
			call_user_func([
				$this,
				$this->_accessor[$key],
			], $value);
			return $this;
		}
		return $this->_internal_set($key, $value);
	}

	public function __unset($key): void {
		$this->__set($key, null);
	}

	public function store(): self {
		$this->application->logger->debug('{method} called', [
			'method' => __METHOD__,
		]);
		$settings = $this->application->modelSingleton(Settings::class);
		foreach ($this->_changed as $key => $value) {
			if ($this->optionBool('debug_save')) {
				$this->application->logger->debug('{method} Saving {key}={value} ({type})', [
					'method' => __METHOD__,
					'key' => $key,
					'value' => PHP::dump($value),
					'type' => type($value),
				]);
			}
			$settings->set($key, $value);
		}
		$this->callHook('stored');
		$this->_changed = [];
		return parent::store();
	}

	public function access_class_member($name, $class, $set = null) {
		if ($set === null) {
			if (array_key_exists($name, $this->_access_cache)) {
				return $this->_access_cache[$name];
			}
			$value = $this->_internal_get($name);
			if (is_numeric($value) && intval($value) !== 0) {
				try {
					return $this->_access_cache[$name] = ORMBase::factory($class, $value)->fetch();
				} catch (Exception_ORM_NotFound $e) {
				}
				return $this->_access_cache[$name] = null;
			}
		}
		if ($set instanceof $class) {
			$this->_access_cache[$name] = $set;
			return $this->_internal_set($name, $set->id());
		}
		unset($this->_access_cache[$name]);
		return $this->_internal_set($name, $set);
	}
}