<?php
declare(strict_types=1);
/**
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Command\SimpleCommand;

/**
 *
 * @author kent
 *
 */
class SimpleTestCommand extends SimpleCommand
{
	public function initialize(): void
	{
		parent::initialize();
		$this->argumentTypes += [
			'file' => 'file',
			'dir' => 'dir',
		];
	}

	public function run(): int
	{
		$class = $this->optionString('testThrow');
		if ($class) {
			throw new $class();
		}
		return $this->optionInt('testExitCode');
	}
}
