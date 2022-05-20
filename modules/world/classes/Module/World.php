<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage address
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_World extends Module_JSLib {
	protected $javascript_paths = [
		'/share/world/js/module.world.js',
	];

	protected array $model_classes = [
		'zesk\\Currency',
		'zesk\\City',
		'zesk\\County',
		'zesk\\Country',
		'zesk\\Language',
		'zesk\\Province',
	];

	public function hook_head(Request $request, Response $response, Template $template): void {
		$currency = $this->call_hook_arguments('currency', [], null);
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
