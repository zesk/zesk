<?php
/**
 * 
 */
namespace zesk;

/**
 * Output all globals
 * @category Debugging
 */
class Command_Globals extends Command_Base {
	/**
	 * 
	 * @var string
	 */
	protected $help = "Output all globals.";
	
	/**
	 * 
	 * @var array
	 */
	protected $option_types = array(
		'format' => 'string',
		'*' => 'string'
	);
	
	/**
	 * 
	 * @var array
	 */
	protected $option_help = array(
		'format' => "Output format: html, php, json, text, serialize",
		"*" => "globals to output"
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	function run() {
		global $zesk;
		PHP::dump_settings_one();
		$globals = $zesk->configuration->to_array();
		ksort($globals);
		$args = $this->arguments_remaining(true);
		if (count($args) > 0) {
			$globals = arr::filter($globals, $args);
		}
		$this->render_format($globals);
	}
}
