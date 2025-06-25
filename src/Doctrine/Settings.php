<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage system
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use zesk\Application;
use zesk\Application\Hooks;
use zesk\bash;
use zesk\Configuration;
use zesk\EmptyHookable;
use zesk\Exception\ClassNotFound;
use zesk\Exception\Deprecated;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\HookMethod;
use zesk\Interface\MetaInterface;
use zesk\Interface\SettingsInterface;
use zesk\Types;

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see    Class_Settings
 */
class Settings extends EmptyHookable implements MetaInterface, SettingsInterface
{
	/**
	 * Enable debugging on the Settings save step
	 *
	 * Option value is bool
	 *
	 * @var string
	 */
	public const HOOK_FILTER = __CLASS__ . '::filter';

	protected EntityManager $em;

	public const OPTION_ENTITY_MANAGER = 'entityManagerName';

	public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
		$this->em = $application->entityManager($this->optionString(self::OPTION_ENTITY_MANAGER));
	}

	/**
	 * configured Hook
	 */
	/**
	 * @param Application $application
	 * @return void
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	#[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
	public static function configured(Application $application): void
	{
		$settings = $application->settings();
		$variables = $settings->variables();
		$depends = [];
		$variables = $application->invokeTypedFilters(self::HOOK_FILTER, $variables, [
			$variables,
			$application,
		], 0);
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
	public function __get(int|string $name): mixed
	{
		return $this->application->configuration->getPath($name);
	}

	/**
	 * Same as __get with a default
	 *
	 * @param int|string $key
	 * @param mixed|null $default
	 * @return mixed
	 * @see Model::get()
	 */
	public function get(int|string $key, mixed $default = null): mixed
	{
		return $this->application->configuration->getPath($key, $default);
	}

	/**
	 * Same as __get with a default
	 *
	 * @param int|string $key
	 * @return mixed
	 * @see Model::get()
	 */
	public function has(int|string $key): bool
	{
		return $this->application->configuration->has($key, true);
	}

	/**
	 * @param int|string $key
	 * @return bool
	 * @see Model::__isset()
	 */
	public function __isset(int|string $key): bool
	{
		return $this->application->configuration->pathExists($key);
	}

	/**
	 * Global to save
	 *
	 * @see Model::__set($member, $value)
	 */
	public function __set(int|string $key, mixed $value): void
	{
		SettingsValue::register($this->application, $key, $value);
	}

	/**
	 * Global to save
	 *
	 * @param int|string $key
	 * @param mixed|null $value
	 * @return self
	 * @see Model::set($member, $value)
	 */
	public function set(int|string $key, mixed $value = null): self
	{
		$this->__set($key, $value);
		return $this;
	}

	/**
	 *
	 */
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function meta(string $name): mixed
	{
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
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function setMeta(string $name, mixed $value): self
	{
		SettingsValue::register($this->application, $name, $value);
		return $this;
	}

	protected function settingsValueRepository(): EntityRepository
	{
		return $this->em->getRepository(SettingsValue::class);
	}

	/**
	 *
	 * @return $this
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function clearMeta(): self
	{
		foreach ($this->settingsValueRepository()->findAll() as $value) {
			$this->em->remove($value);
		}
		$this->em->flush();
		return $this;
	}

	public function variables(): array
	{
		$result = [];
		foreach ($this->settingsValueRepository()->findAll() as $value) {
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
	public function deleteMeta(array|string $name): self
	{
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
	public function deprecated(string $old_setting, string $new_setting)
	{
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
	public function prefixUpdated(string $oldPrefix, string $newPrefix): int
	{
		$em = $this->application->entityManager();
		$query = $em->createQuery('UPDATE ' . SettingsValue::class . ' SET name=REPLACE(name, :old, :new) WHERE name LIKE :oldPattern)');
		$rowCount = $query->execute([
			'old' => $oldPrefix,
			'new' => $newPrefix,
			'oldPattern' => $oldPrefix . '%',
		]);
		if ($rowCount > 0) {
			$this->application->notice('Updated {rowCount} settings from {old_prefix} to use new prefix {new_prefix}', [
				'rowCount' => $rowCount,
				'old_prefix' => $oldPrefix,
				'new_prefix' => $newPrefix,
			]);
		}
		return $rowCount;
	}
}
