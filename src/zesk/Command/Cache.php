<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use zesk\Exception\ClassNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;

/**
 * Cache commands. Takes a single argument: "clear" or "print" to print the class in the application.
 *
 * @category Management
 * @author kent
 *
 */
class Cache extends SimpleCommand
{
	protected array $shortcuts = ['cache'];

	protected array $option_types = [
		'*' => 'string',
	];

	protected function run(): int
	{
		if ($this->hasArgument()) {
			do {
				$arg = $this->getArgument('command');
				$this->run_arg($arg);
			} while ($this->hasArgument());
		} else {
			$this->run_arg('print');
		}
		return $this->hasErrors() ? 1 : 0;
	}

	protected function run_arg($arg)
	{
		$methods = ['clear' => [$this, '_exec_clear'], 'print' => [$this, '_exec_print']];
		$method = $methods[$arg] ?? null;
		if ($method) {
			return call_user_func($method);
		}
		$this->error('No such command {arg}', [
			'arg' => $arg,
		]);
		return 1;
	}

	protected function _exec_print(): int
	{
		print(get_class($this->application->cacheItemPool()) . "\n");
		return 0;
	}

	/**
	 * @return int
	 * @throws ClassNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 */
	protected function _exec_clear(): int
	{
		$this->application->cacheClear();
		return 0;
	}
}
