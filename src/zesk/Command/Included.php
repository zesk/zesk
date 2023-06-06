<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use zesk\Command;

/**
 * Display a list of all included files so far
 * @category Debugging
 */
class Included extends Command {
	public function run(): int {
		echo implode("\n", get_included_files()) . "\n";
		return 0;
	}
}
