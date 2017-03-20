<?php
/**
 *
 *
 */
namespace zesk;

/**
 * Output the elapsed time of the script so far. Useful when doing:
 *
 * zesk command1 command2 runtime
 *
 * @category Optimization
 * @author kent
 */
class Command_Runtime extends Command {
	function run() {
		$zesk = $this->application->zesk;
		$delta = microtime(true) - $zesk->initialization_time;
		$digits = $delta < 0.001 ? ($delta < 0.000001 ? /* yeah right */ 9 : 6) : 3;
		echo sprintf("%." . $digits . "f", $delta) . " sec\n";
		return 0;
	}
}
