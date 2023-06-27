<?php
declare(strict_types=1);

namespace zesk\Command;

class Testlike extends SimpleCommand
{
	protected array $shortcuts = ['test-like'];

	public function run(): int
	{
		return $this->application->development() ? 1 : 0;
	}
}
