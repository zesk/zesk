<?php
declare(strict_types=1);

namespace zesk\Polyglot\Command;

use zesk\Command_Base;
use zesk\Polyglot\Update as UpdateObject;

/**
 *
 * @author kent
 *
 */
class Update extends Command_Base {
	protected function run(): int {
		UpdateObject::cron_minute($this->application);
		return 0;
	}
}
