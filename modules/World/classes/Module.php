<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

use zesk\Interface_Module_Head;
use zesk\Module_JSLib;
use zesk\ORM\Interface_Schema_Updated;
use zesk\Request;
use zesk\Response;
use zesk\Template;

/**
 *
 * @author kent
 *
 */
class Module extends Module_JSLib implements Interface_Module_Head, Interface_Schema_Updated {
	protected array $javascript_paths = [
		'/share/world/js/module.world.js',
	];

	protected array $modelClasses = [
		Currency::class,
		City::class,
		County::class,
		Country::class,
		Language::class,
		Province::class,
	];

	public function hook_head(Request $request, Response $response, Template $template): void {
		$currency = $this->callHookArguments('currency');
		/* @var $currency Currency */
		if ($currency instanceof Currency) {
			$this->javascript_settings += [
				'currency' => $currency->members('id;precision;format;name;code;symbol;fractional;fractional_units'),
			];
		}
		parent::hook_head($request, $response, $template);
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
			World_Bootstrap_Country::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_language) {
			$this->application->logger->debug('{method} World_Bootstrap_Language', $__);
			World_Bootstrap_Language::factory($this->application)->bootstrap();
		}
		if ($bootstrap || $bootstrap_currency) {
			$this->application->logger->debug('{method} World_Bootstrap_Currency', $__);
			World_Bootstrap_Currency::factory($this->application)->bootstrap();
		}
		$this->application->logger->debug('{method} ended', $__);

		Language::clean_table($this->application);
	}
}
