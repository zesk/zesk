<?php
/**
 * Output the elapsed time of the script so far. Useful when doing:
 *
 * zesk command1 command2 runtime
 *
 * @category Optimization
 */
function zesk_command_runtime() {
	$delta = microtime(true) - zesk::get('init');
	$digits = $delta < 0.001 ? ($delta < 0.000001 ? /* yeah right */ 9 : 6) : 3;
	echo sprintf("%." . $digits . "f", $delta) . " sec\n";
}
