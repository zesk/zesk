<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\Configuration;
use zesk\Exception\ClassNotFound;
use zesk\Model;
use zesk\ORM\Exception\ORMNotFound;
use zesk\PHP;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class SettingsModel extends Model
{
	protected array $_changed = [];

	protected array $_accessor = [];

	protected array $_access_cache = [];

	protected array $ignore_variables = [];

	/**
	 * Array of key => default
	 *
	 * @var array $variables
	 */
	protected array $variables = [];

	/**
	 *
	 */
	protected Configuration $configuration;

	/**
	 *
	 * @var array
	 */
	protected array $state = [];

	/**
	 *
	 */
	public function hook_construct(): void
	{
		$this->configuration = $this->application->configuration;
		$this->inheritConfiguration();
	}

	/**
	 * @return array
	 */
	public function ignoreVariables(): array
	{
		return $this->ignore_variables;
	}

	/**
	 *
	 * @param string|array $mixed
	 * @return $this
	 */
	public function addIgnoreVariables(string|array $mixed): self
	{
		$mixed = Types::toList($mixed);
		foreach ($mixed as $item) {
			if (!in_array($item, $this->ignore_variables)) {
				$this->ignore_variables[] = $item;
			}
		}
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function allowVariables(): array
	{
		return $this->variables;
	}

	/**
	 *
	 * @param mixed $mixed
	 */
	public function addAllowVariable(string|array $mixed): self
	{
		$mixed = Types::toList($mixed);
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

	public function __isset(int|string $key): bool
	{
		if (array_key_exists($key, $this->_changed)) {
			return true;
		}
		if (array_key_exists($key, $this->variables)) {
			return $this->application->configuration->pathExists($key);
		}
		return isset($this->state[$key]);
	}

	private function _ignore_variable(string $key): bool
	{
		return in_array($key, $this->ignore_variables);
	}

	protected function _internal_set(int|string $key, mixed $value): void
	{
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
	}

	/**
	 * Get a value from this model
	 *
	 * @param string $key
	 * @return mixed
	 */
	protected function _internal_get(string $key): mixed
	{
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

	public function variables(): array
	{
		$result = $this->configuration->getPath($this->variables);
		foreach ($result as $k => $v) {
			if ($v instanceof Configuration) {
				$result[$k] = Types::toArray($v->toArray());
			}
		}
		return $result;
	}

	public function __get(int|string $key): mixed
	{
		if (array_key_exists($key, $this->_accessor)) {
			return call_user_func([
				$this,
				$this->_accessor[$key],
			]);
		}
		return $this->_internal_get($key);
	}

	public function __set(int|string $key, mixed $value): void
	{
		if (array_key_exists($key, $this->_accessor)) {
			call_user_func([
				$this,
				$this->_accessor[$key],
			], $value);
			return;
		}
		$this->_internal_set($key, $value);
	}

	public function __unset($key): void
	{
		$this->__set($key, null);
	}

	/**
	 * @throws ClassNotFound
	 */
	public function store(): self
	{
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
					'type' => Types::type($value),
				]);
			}
			$settings->set($key, $value);
		}
		$this->callHook('stored');
		$this->_changed = [];
		return parent::store();
	}

	public function access_class_member($name, $class, $set = null)
	{
		if ($set === null) {
			if (array_key_exists($name, $this->_access_cache)) {
				return $this->_access_cache[$name];
			}
			$value = $this->_internal_get($name);
			if (is_numeric($value) && intval($value) !== 0) {
				try {
					return $this->_access_cache[$name] = $this->application->ormFactory($class, $value)->fetch();
				} catch (ORMNotFound) {
				}
				return $this->_access_cache[$name] = null;
			}
		}
		if ($set instanceof $class) {
			$this->_access_cache[$name] = $set;
			$this->_internal_set($name, $set->id());
		}
		unset($this->_access_cache[$name]);
		$this->_internal_set($name, $set);
	}
}
