<?php
namespace zesk;

/**
 * Display a list of all included files so far
 * @category Debugging
 */
class Command_Included extends Command_Base {
	protected $help = "Display a list of all included files so far.";

	public function run() {
		echo implode("\n", get_included_files()) . "\n";
	}
}
