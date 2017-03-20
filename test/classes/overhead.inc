<?php
/**
 * @no_buffer true
 * @author kent
 * @sandbox false
 */
class test_overhead extends Test_Unit {
	function test_usage() {
		$test_limit = 10 * 1024 * 1024; // 10M
		$users = array();
		$start = memory_get_usage();
		$stop = $start + $test_limit;
		// echo "Start=$start, Stop=$stop\n";
		do {
			$users[] = new User(1);
			$current = memory_get_usage();
			// echo "Current=$current\n";
			$delta = $current - $start;
			// $this->log(count($users) . " users fit in $delta (" . Number::format_bytes($delta) . ")");
			// echo "$current < $stop, " . ($current < $stop) . "\n";
		} while ($current < $stop);
		$nusers = count($users);
		$this->log("{nusers} users fit in {bytes}, or {per_user} per user", array(
			"nusers" => $nusers,
			"bytes" => Number::format_bytes($test_limit),
			"per_user" => Number::format_bytes($test_limit / $nusers)
		));
	}
	private function run_php_sandbox($sandbox) {
		$php = zesk::which("php");
		ob_start();
		$result = system("$php $sandbox");
		ob_end_clean();
		return $result;
	}

	/**
	 * @no_buffer true
	 */
	function test_kernel_usage() {
		$sandbox = $this->test_sandbox("run.php");
		file_put_contents($sandbox, "<?php\necho memory_get_usage();");
		$result = $this->run_php_sandbox($sandbox);
		$this->assert(is_numeric($result));
		$raw_usage = intval($result);
		$this->log("Raw PHP usage is $raw_usage");
		file_put_contents($sandbox, "<?php\nrequire_once '" . ZESK_ROOT . "zesk.inc'; echo memory_get_usage();");
		$result = $this->run_php_sandbox($sandbox);

		$this->log("Raw PHP usage is $raw_usage");
		$zesk_usage = intval($result);
		$this->log("Zesk PHP usage is $zesk_usage");
		$zesk_usage = intval($result);
		$delta = $zesk_usage - $raw_usage;
		$this->log("Zesk Overhead is " . $delta . " " . Number::format_bytes($delta));
	}
}
