<?php
class Test_System extends Test_Unit {

	function test_critical_alert() {
		$message = null;
		system::critical_alert($message);
	}

	function test_daemon_host_id() {
		system::daemon_host_id();
	}

	function test_group_host_id() {
		system::group_host_id();
	}

	function test_growl() {
		$message = null;
		system::growl($message);
	}

	function test_host_id() {
		system::host_id();
	}

	function test_load_averages() {
		$default = null;
		system::load_averages($default);
		echo basename(__FILE__) . ": success\n";
	}

	function test_process_id() {
		system::process_id();
	}

	function test_services_status() {
		if (!is_dir('/service')) {
			echo "No /service directory. Skipping.";
			return;
		}
		$services = system::services_status();
		$this->assert(is_array($services));
		foreach ($services as $name => $service) {
			$this->assert($name === $service['name']);
			$this->assert(array_key_exists('seconds', $service));
			$this->assert(array_key_exists('state', $service));
			$this->assert($service['state'] === 'up' || $service['state'] === 'down');
		}
	}

	function test_uname() {
		system::uname();
	}

	function test_volume_info() {
		$info = system::volume_info();
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));
		assert(array_key_exists('path', $slash));

		$info = system::volume_info('/');
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));

		$info = system::volume_info('/not-a-volume');
		assert(is_array($info));
		assert(count($info) === 0);

		echo basename(__FILE__) . ": success\n";
	}
}
