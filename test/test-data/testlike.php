<?php
declare(strict_types=1);

namespace zesk;

class Testlike extends Command_Base {
	protected array $shortcuts = ['test-like'];

	public function run(): int {
		return $this->application->development() ? 1 : 0;
	}
}
