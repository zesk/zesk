<?php declare(strict_types=1);
namespace zesk;

/**
 * Display the hostname according to Zesk
 *
 * @category Debugging
 * @alias uname
 */
class Command_Host extends Command_Base {
	public function run(): int {
		echo System::uname() . "\n";
		return 0;
	}
}
