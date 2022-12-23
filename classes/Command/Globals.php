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
	protected array $shortcuts = ['globals', 'g'];

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
		'format' => 'Output format: html, php, json, text, serialize',
		'*' => 'globals to output',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	public function run(): int {
		PHP::dump_settings_one();
		$globals = $this->application->configuration->toArray();
		ksort($globals);
		$args = $this->argumentsRemaining();
		if (count($args) > 0) {
			$globals = ArrayTools::filter($globals, $args);
		}
		$this->renderFormat($globals);
		return 0;
	}
}
