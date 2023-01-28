<?php declare(strict_types=1);

namespace zesk;

class Test2Command extends Command {
	protected array $shortcuts = ['testomatic2'];

	/**
	 * @return int
	 */
	protected function run(): int {
		echo 'no';
		return 0;
	}
}
