<?php
declare(strict_types=1);

namespace testZesk;

use zesk\Command;

class Test2Command extends Command
{
	protected array $shortcuts = ['testomatic2'];

	/**
	 * @return int
	 */
	protected function run(): int
	{
		echo 'no';
		return 0;
	}
}
