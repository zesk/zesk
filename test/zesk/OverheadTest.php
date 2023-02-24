<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);
/**
 * @no_buffer true
 * @author kent
 * @sandbox false
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class OverheadTest extends UnitTest {
	protected array $load_modules = [
		'Doctrine',
	];

	public function test_usage(): void {
		$test_limit = 10 * 1024 * 1024; // 10M
		$servers = [];
		$start = memory_get_usage();
		$stop = $start + $test_limit;
		// echo "Start=$start, Stop=$stop\n";
		do {
			$servers[] = new TestModel($this->application, 1);
			$current = memory_get_usage();
			// echo "Current=$current\n";
			// $delta = $current - $start;
			// $this->log(count($users) . " users fit in $delta (" . Number::format_bytes($delta) . ")");
			// echo "$current < $stop, " . ($current < $stop) . "\n";
		} while ($current < $stop);
		$nObjects = count($servers);
		$this->log('{nObjects} objects fit in {bytes}, or {perObject} per object', [
			'nObjects' => $nObjects,
			'bytes' => Number::formatBytes($this->application->locale, $test_limit),
			'perObject' => Number::formatBytes($this->application->locale, intval($test_limit / $nObjects)),
		]);
	}

	private function run_php_sandbox($sandbox): bool|string {
		$php = $this->application->paths->which('php');
		ob_start();
		$result = system("$php $sandbox");
		ob_end_clean();
		return $result;
	}

	/**
	 * @no_buffer true
	 */
	public function test_kernel_usage(): void {
		$sandbox = $this->test_sandbox('run.php');
		file_put_contents($sandbox, "<?php\necho memory_get_usage();");
		$result = $this->run_php_sandbox($sandbox);
		$this->assertIsNumeric($result);
		$raw_usage = intval($result);
		$this->log("Raw PHP usage is $raw_usage");

		file_put_contents($sandbox, "<?php\nrequire_once '" . $this->application->path('zesk.application.php') . "';\necho memory_get_usage();");
		$result = $this->run_php_sandbox($sandbox);
		$this->assertIsNumeric($result);
		$usage = intval($result);
		$this->log("Zesk PHP usage is $usage");

		$delta = $usage - $raw_usage;
		$this->log('Zesk Overhead is ' . $delta . ' ' . Number::formatBytes($this->application->locale, $delta));
	}
}
