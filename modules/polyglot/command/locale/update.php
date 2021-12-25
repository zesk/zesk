<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Command_Locale_Update extends Command_Base {
	protected function run(): void {
		PolyGlot_Update::cron_minute($this->application);
	}
}
