<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use zesk\Command;

/**
 * Output the current working directory
 *
 * @category Debugging
 * @param array $args
 * @return array
 */
class CWD extends Command {
	protected array $shortcuts = ['cwd'];

	public function run(): int {
		echo getcwd() . "\n";
		return 0;
	}
}
