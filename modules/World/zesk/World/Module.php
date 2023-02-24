<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

use zesk\Module as BaseModule;
use zesk\ORM\Interface\SchemaUpdatedInterface;

use zesk\Doctrine\Module as DoctrineModule;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule implements SchemaUpdatedInterface {
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

	public function initialize(): void {
		parent::initialize();
		$this->application->doctrineModule()->addPath($this->path('zesk/World'));
	}

	public function hook_schema_updated(): void {
		$__ = [
			'method' => __METHOD__,
		];
		$bootstrap = $this->optionBool('bootstrap_all');
		$bootstrap_country = $this->optionBool('bootstrap_country');
		$bootstrap_currency = $this->optionBool('bootstrap_currency');
		$bootstrap_language = $this->optionBool('bootstrap_language');
		$this->application->logger->debug('{method} begin', $__);
		if ($bootstrap || $bootstrap_country) {
			$this->application->logger->debug('{method} World_Bootstrap_Country', $__);
			Bootstrap_Country::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_language) {
			$this->application->logger->debug('{method} World_Bootstrap_Language', $__);
			Bootstrap_Language::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_currency) {
			$this->application->logger->debug('{method} World_Bootstrap_Currency', $__);
			Bootstrap_Currency::factory($this->application)->bootstrap();
		}
		$this->application->logger->debug('{method} ended', $__);

		Language::clean_table($this->application);
	}
}
