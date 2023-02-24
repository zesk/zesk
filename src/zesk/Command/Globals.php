<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use zesk\ArrayTools;
use zesk\PHP;

/**
 * Output all globals
 * @category Debugging
 */
class Globals extends SimpleCommand {
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
