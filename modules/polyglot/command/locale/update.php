<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Command_Locale_Update extends Command_Base {
	protected function run() {
		PolyGlot_Update::cron_minute($this->application);
	}
}
