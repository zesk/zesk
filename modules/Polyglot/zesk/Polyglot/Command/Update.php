<?php
declare(strict_types=1);

namespace zesk\Polyglot\Command;

use zesk\Command\SimpleCommand;
use zesk\Polyglot\Update as UpdateObject;

/**
 *
 * @author kent
 *
 */
class Update extends SimpleCommand
{
	protected function run(): int
	{
		UpdateObject::cron_minute($this->application);
		return 0;
	}
}
