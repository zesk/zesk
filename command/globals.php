<?php declare(strict_types=1);
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
	protected array $option_types = [
		'format' => 'string',
		'*' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	protected array $option_help = [
		'format' => "Output format: html, php, json, text, serialize",
		"*" => "globals to output",
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	public function run(): void {
		PHP::dump_settings_one();
		$globals = $this->application->configuration->to_array();
		ksort($globals);
		$args = $this->arguments_remaining(true);
		if (count($args) > 0) {
			$globals = ArrayTools::filter($globals, $args);
		}
		$this->render_format($globals);
	}
}
