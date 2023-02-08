<?php declare(strict_types=1);
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
class Command_RunTime extends Command {
	protected array $shortcuts = ['runtime'];

	public function run(): int {
		$time = $this->application->initializationTime();
		$delta = microtime(true) - $time;
		$digits = $delta < 0.001 ? ($delta < 0.000001 ? /* yeah right */ 9 : 6) : 3;
		echo sprintf('%.' . $digits . 'f', $delta) . " sec\n";
		return 0;
	}
}
