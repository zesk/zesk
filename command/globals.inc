<?php

/**
 * Output all globals
 * @category Debugging
 */
class Command_Globals extends Command_Base {
	protected $help = "Output all globals.";
	protected $option_types = array(
		'format' => 'string',
		'application' => 'string',
		'*' => 'string'
	);
	protected $option_help = array(
		'format' => "Output format: html, php, json, text, serialize",
		'application' => "Load alternate application context.",
		"*" => "globals to output"
	);
	function run() {
		if ($this->option('application')) {
			zesk::application_class($this->option('application'));
			$app = Application::instance();
		}
		php::dump_settings_one();
		$globals = zesk::get();
		ksort($globals);
		$args = $this->arguments_remaining(true);
		if (count($args) > 0) {
			$globals = arr::filter($globals, $args);
		}
		$this->render_format($globals);
	}
}