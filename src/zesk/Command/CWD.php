<?php
declare(strict_types=1);
/**
 *
 *
 */
namespace zesk;

/**
 * Output the current working directory
 *
 * @category Debugging
 * @param array $args
 * @return array
 */
class Command_CWD extends Command {
	protected array $shortcuts = ['cwd'];

	public function run(): int {
		echo getcwd() . "\n";
		return 0;
	}
}
