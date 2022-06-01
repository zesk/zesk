<?php
declare(strict_types=1);

namespace zesk;

/**
 * Cache commands. Takes a single argument: "clear" or "print" to print the class in the application.
 *
 * @category Management
 * @author kent
 *
 */
class Command_Cache extends Command_Base {
	protected array $option_types = [
		'*' => 'string',
	];

	protected function run(): int {
		if ($this->has_arg()) {
			do {
				$arg = $this->get_arg('command');
				$this->run_arg($arg);
			} while ($this->has_arg());
		} else {
			$this->run_arg('print');
		}
		return $this->has_errors() ? 1 : 0;
	}

	protected function run_arg($arg) {
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

	protected function _exec_print(): int {
		print(get_class($this->application->cache) . "\n");
		return 0;
	}

	protected function _exec_clear(): int {
		$this->application->cacheClear();
		return 0;
	}
}
