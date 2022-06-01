<?php declare(strict_types=1);

/**
 *
 *
 */
namespace zesk;

/**
 * Output all command arguments as a JSON-encoded array
 *
 * @category Debugging
 * @param array $arguments
 * @return unknown
 */
class Command_Arguments extends Command {
	public array $option_types = [
		'*' => 'string',
	];

	protected function run(): int {
		$arguments = $this->arguments_remaining(true);
		echo json_encode($arguments) . "\n";
		return 0;
	}
}
