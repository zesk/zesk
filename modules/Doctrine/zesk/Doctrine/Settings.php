<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Throwable;
use zesk\Application;
use zesk\Application\Hooks;
use zesk\bash;
use zesk\Configuration;
use zesk\Exception\ClassNotFound;
use zesk\Exception\Deprecated;
use zesk\Exception\NotFoundException;
use zesk\Exception\SyntaxException;
use zesk\Hookable;
use zesk\HookMethod;
use zesk\Interface\MetaInterface;
use zesk\Interface\SettingsInterface;
use zesk\Types;

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see Class_Settings
 */
class Settings extends Hookable implements MetaInterface, SettingsInterface {
	/**
	 * Enable debugging on the Settings save step
	 *
	 * Option value is bool
	 *
	 * @var string
	 */
	public const HOOK_FILTER = __CLASS__ . '::filter';

	/**
	 * configured Hook
	 */
	/**
	 * @param Application $application
	 * @return void
	 * @throws ClassNotFound
	 */
	#[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
	public static function configured(Application $application): void {
		$settings = $application->settings();
		$variables = $settings->variables();
		$depends = [];
		$variables = $application->invokeTypedFilters(self::HOOK_FILTER, $variables, [$variables, $application], 0);
		foreach ($variables as $name => $value) {
			$value = bash::substitute($value, $settings, $depends);
			$path = explode(Configuration::key_separator, $name);
			$application->configuration->setPath($path, $value);
		}
	}

	/**
	 * Override get to retrieve from global state
	 *
	 * @param int|string $name Setting to retrieve
	 * @return mixed
	 */
	public function __get(int|string $name): mixed {
		return $this->application->configuration->getPath($name);
	}

	/**
	 * Same as __get with a default
	 *
	 * @param int|string $name
	 * @param mixed|null $default
	 * @return mixed
	 * @see Model::get()
	 */
	public function get(int|string $name, mixed $default = null): mixed {
		return $this->application->configuration->getPath($name, $default);
	}

	/**
	 * Same as __get with a default
	 *
	 * @param int|string $name
	 * @return mixed
	 * @see Model::get()
	 */
	public function has(int|string $name): bool {
		return $this->application->configuration->has($name, true);
	}

	/**
	 * @param int|string $name
	 * @return bool
	 * @see Model::__isset()
	 */
	public function __isset(int|string $name): bool {
		return $this->application->configuration->pathExists($name);
	}

	/**
	 * Global to save
	 *
	 * @see Model::__set($member, $value)
	 */
	public function __set(int|string $name, mixed $value): void {
		SettingsValue::register($this->application, $name, $value);
	}

	/**
	 * Global to save
	 *
	 * @param int|string $name
	 * @param mixed|null $value
	 * @return self
	 * @see Model::set($member, $value)
	 */
	public function set(int|string $name, mixed $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	/**
	 *
	 */
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function meta(string $name): mixed {
		try {
			return SettingsValue::find($this->application, $name)->value;
		} catch (NotFoundException) {
			return null;
		}
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setMeta(string $name, mixed $value): self {
		SettingsValue::register($this->application, $name, $value);
		return $this;
	}

	/**
	 *
	 * @return $this
	 */
	public function clearMeta(): self {
		foreach ($this->em->getRepository(SettingsValue::class)->findAll() as $value) {
			$this->em->remove($value);
		}
		$this->em->flush();
		return $this;
	}

	public function variables(): array {
		$result = [];
		foreach ($this->em->getRepository(SettingsValue::class)->findAll() as $value) {
			/* @var $value SettingsValue */
			$result[$value->name] = $value->value;
		}
		return $result;
	}

	/**
	 *
	 * @param array|string $name
	 * @return $this
	 * @see MetaInterface::deleteData()
	 */
	public function deleteMeta(array|string $name): self {
		foreach (Types::toArray($name) as $item) {
			$this->__set($item, null);
		}
		return $this;
	}

	/**
	 * Call this when you change your setting names
	 *
	 * @param string $old_setting
	 * @param string $new_setting
	 * @return $this|void
	 * @throws Deprecated
	 */
	public function deprecated(string $old_setting, string $new_setting) {
		if (!$this->__isset($old_setting)) {
			return;
		}
		$this->application->deprecated(__CLASS__ . "::deprecated(\"$old_setting\", \"$new_setting\")");
		if ($this->__isset($new_setting)) {
			$this->__set($old_setting, null);
			return $this;
		}
		$this->__set($new_setting, $this->__get($old_setting));
		$this->__set($old_setting, null);
		return $this;
	}

	/**
	 *
	 * @param string $oldPrefix
	 * @param string $newPrefix
	 * @return integer
	 */
	public function prefixUpdated(string $oldPrefix, string $newPrefix): int {
		$em = $this->application->entityManager();
		$query = $em->createQuery('UPDATE ' . SettingsValue::class . ' SET name=REPLACE(name, :old, :new) WHERE name LIKE :oldPattern)');
		$rowCount = $query->execute(['old' => $oldPrefix, 'new' => $newPrefix, 'oldPattern' => $oldPrefix . '%']);
		if ($rowCount > 0) {
			$this->application->notice('Updated {rowCount} settings from {old_prefix} to use new prefix {new_prefix}', [
				'rowCount' => $rowCount, 'old_prefix' => $oldPrefix, 'new_prefix' => $newPrefix,
			]);
		}
		return $rowCount;
	}
}
