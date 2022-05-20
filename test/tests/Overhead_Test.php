<?php declare(strict_types=1);
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
class Overhead_Test extends Test_Unit {
	protected array $load_modules = [
		'ORM',
	];

	public function test_usage(): void {
		$test_limit = 10 * 1024 * 1024; // 10M
		$users = [];
		$start = memory_get_usage();
		$stop = $start + $test_limit;
		// echo "Start=$start, Stop=$stop\n";
		do {
			$users[] = new User($this->application, 1);
			$current = memory_get_usage();
			// echo "Current=$current\n";
			$delta = $current - $start;
			// $this->log(count($users) . " users fit in $delta (" . Number::format_bytes($delta) . ")");
			// echo "$current < $stop, " . ($current < $stop) . "\n";
		} while ($current < $stop);
		$nusers = count($users);
		$this->log('{nusers} users fit in {bytes}, or {per_user} per user', [
			'nusers' => $nusers,
			'bytes' => Number::format_bytes($this->application->locale, $test_limit),
			'per_user' => Number::format_bytes($this->application->locale, $test_limit / $nusers),
		]);
	}

	private function run_php_sandbox($sandbox) {
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
		$this->assert_is_numeric($result);
		$raw_usage = intval($result);
		$this->log("Raw PHP usage is $raw_usage");

		file_put_contents($sandbox, "<?php\nrequire_once '" . $this->application->path('zesk.application.php') . "';\necho memory_get_usage();");
		$result = $this->run_php_sandbox($sandbox);
		$this->assert_is_numeric($result);
		$usage = intval($result);
		$this->log("Zesk PHP usage is $usage");

		$delta = $usage - $raw_usage;
		$this->log('Zesk Overhead is ' . $delta . ' ' . Number::format_bytes($this->application->locale, $delta));
	}
}
