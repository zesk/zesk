<?php
declare(strict_types=1);

namespace testZesk;

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
