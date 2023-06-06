<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use zesk\System;
use zesk\Command;

/**
 * Display the hostname according to Zesk
 *
 * @category Debugging
 * @alias uname
 */
class Host extends Command {
	/**
	 * @var array
	 */
	protected array $shortcuts = ['host'];

	public function run(): int {
		echo System::uname() . "\n";
		return 0;
	}
}
