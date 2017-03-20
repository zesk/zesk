<?php
class Test_System extends Test_Unit {

	function test_critical_alert() {
		$message = null;
		zesk\System::critical_alert($message);
	}

	function test_daemon_host_id() {
		zesk\System::daemon_host_id();
	}

	function test_group_host_id() {
		zesk\System::group_host_id();
	}

	function test_growl() {
		$message = null;
		zesk\System::growl($message);
	}

	function test_host_id() {
		zesk\System::host_id();
	}

	function test_load_averages() {
		$default = null;
		zesk\System::load_averages($default);
		echo basename(__FILE__) . ": success\n";
	}

	function test_process_id() {
		zesk\System::process_id();
	}

	function test_services_status() {
		if (!is_dir('/service')) {
			echo "No /service directory. Skipping.";
			return;
		}
		$services = zesk\System::services_status();
		$this->assert(is_array($services));
		foreach ($services as $name => $service) {
			$this->assert($name === $service['name']);
			$this->assert(array_key_exists('seconds', $service));
			$this->assert(array_key_exists('state', $service));
			$this->assert($service['state'] === 'up' || $service['state'] === 'down');
		}
	}

	function test_uname() {
		zesk\System::uname();
	}

	function test_volume_info() {
		$info = zesk\System::volume_info();
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));
		assert(array_key_exists('path', $slash));

		$info = zesk\System::volume_info('/');
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));

		$info = zesk\System::volume_info('/not-a-volume');
		assert(is_array($info));
		assert(count($info) === 0);

		echo basename(__FILE__) . ": success\n";
	}
}
