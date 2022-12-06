<?php declare(strict_types=1);
namespace zesk;

/**
 * Display a list of all included files so far
 * @category Debugging
 */
class Command_Included extends Command_Base {
	public function run(): void {
		echo implode("\n", get_included_files()) . "\n";
	}
}
