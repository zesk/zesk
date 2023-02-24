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
 * Output all command arguments as a JSON-encoded array
 *
 * @category Debugging
 */
class Arguments extends Command {
	protected array $shortcuts = ['arguments'];

	public array $option_types = [
		'*' => 'string',
	];

	protected function run(): int {
		$arguments = $this->argumentsRemaining();
		echo json_encode($arguments) . "\n";
		return 0;
	}
}
