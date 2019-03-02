<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

class Generator_Apache extends Generator {
	public function render(Host $host) {
		$app = $host->application;
		return $app->theme("webapp/generator/apache", array(
			"webappbin" => $app->webapp_module()->binary(),
		) + $host->option() + $this->template_defaults());
	}

	public function template_defaults() {
		return array(
			"no_webapp" => false,
			"port" => 80,
		);
	}
}
