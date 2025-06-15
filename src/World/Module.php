<?php
/**
 * @package    zesk
 * @subpackage world
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

use zesk\Doctrine\Module as DoctrineModule;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ProtocolException;
use zesk\HookMethod;
use zesk\Module as BaseModule;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule
{
	/**
	 * List of currencies to include (Currency)
	 */
	public const OPTION_INCLUDE_CURRENCY = 'includeCurrency';

	/**
	 * List of countries to include (Currency)
	 */
	public const OPTION_INCLUDE_COUNTRY = 'includeCountry';

	protected string $name = 'World';

	protected array $modelClasses = [
		Currency::class,
		City::class,
		County::class,
		Country::class,
		Language::class,
		Province::class,
	];

	public function initialize(): void
	{
		$this->application->modules->load('doctrine');
		parent::initialize();
	}

	/**
	 * @return void
	 * @throws KeyNotFound
	 * @throws ProtocolException
	 * @see self::schemaWasUpdated()
	 */
	#[HookMethod(handles: DoctrineModule::HOOK_SCHEMA_UPDATED)]
	public function schemaWasUpdated(): void
	{
		$__ = [
			'method' => __METHOD__,
		];
		$bootstrap = $this->optionBool('bootstrap_all');
		$bootstrap_country = $this->optionBool('bootstrap_country');
		$bootstrap_currency = $this->optionBool('bootstrap_currency');
		$bootstrap_language = $this->optionBool('bootstrap_language');
		$this->application->debug('{method} begin', $__);
		if ($bootstrap || $bootstrap_country) {
			$this->application->debug('{method} World_Bootstrap_Country', $__);
			Bootstrap_Country::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_language) {
			$this->application->debug('{method} World_Bootstrap_Language', $__);
			Bootstrap_Language::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_currency) {
			$this->application->debug('{method} World_Bootstrap_Currency', $__);
			Bootstrap_Currency::factory($this->application)->bootstrap();
		}
		$this->application->debug('{method} ended', $__);

		Language::clean_table($this->application);
	}
}
