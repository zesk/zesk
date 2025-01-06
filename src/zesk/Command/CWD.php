<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Command;

use zesk\Command;

/**
 * Output the current working directory
 *
 * @param array $args
 * @return array
 * @category Debugging
 */
class CWD extends Command {
	protected array $shortcuts = ['cwd'];

	public function run(): int
	{
		echo getcwd() . "\n";
		return 0;
	}
}
