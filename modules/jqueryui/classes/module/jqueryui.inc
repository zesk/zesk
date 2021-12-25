<?php declare(strict_types=1);
class Module_jQueryUI extends zesk\Module_JSLib {
	public static $debug = false;

	protected $css_paths = [
		"/share/jqueryui/themes/{theme}/jquery-ui.min.css",
		"/share/jqueryui/themes/{theme}/jquery-ui.css",
	];

	protected $javascript_paths = [
		"/share/jqueryui/jquery-ui.min.js" => [
			"before" => "bootstrap",
			"javascript_after" => '$.widget.bridge(\'uitooltip\', $.ui.tooltip);',
		],
	];

	public function hook_configured(): void {
		if ($this->has_option("extras")) {
			$this->application->logger->warning("Configuration setting Module_jQueryUI::extras is deprecated");
		}
		$this->css_paths = map($this->css_paths, $this->option() + [
			"theme" => "base",
		]);
	}
}
