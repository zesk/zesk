<?php
declare(strict_types=1);

namespace test\zesk;

use zesk\Command;

class TestCommand extends Command
{
	protected array $shortcuts = ['testomatic'];

	/**
	 * @return int
	 */
	protected function run(): int
	{
		echo 'yes';
		return 0;
	}
}
