<?php
class test_alerter extends Test_Unit {

	function test_exists() {
		alerter::exists();
	}

	function test_path_messages() {
		$server = null;
		alerter::path_messages($server);
	}

	function test_alerter_mail() {
		$tracking_file = $this->test_sandbox('tracking-file.txt');
		$after_count = 10;
		$to = null;
		$from = null;
		$subject = null;
		$template_file = null;
		$map = null;
		$cc = false;
		alerter::mail($tracking_file, $after_count, $to, $from, $subject, $template_file, $map, $cc);
	}

	function test_servers() {
		$set = null;
		alerter::servers($set);
	}

	function test_notify() {
		$path = $this->test_sandbox();
		alerter::servers('test1;test2');
		zesk::set('NFS_CLUSTER_HOME', $path);

		$message = "Hello";
		alerter::notify($message);
	}

	function test_notify_intermittently() {
		$root = $this->test_sandbox();

		$servers = alerter::servers();

		zesk::set('NFS_CLUSTER_HOME', $root);

		foreach ($servers as $server) {
			dir::create(alerter::path_messages($server));
		}
		$file = path($root, "test-critical-alert-notify-intermittently");
		if (is_file($file)) {
			unlink($file);
		}

		echo "Notify file is $file\n";
		echo "MONITOR_SERVICES_SERVERS is " . zesk::get('MONITOR_SERVICES_SERVERS') . "\n";

		$this->assert(alerter::delete_all() === true);
		$this->assert(alerter::exists() === false);

		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		$this->assert(alerter::exists() === false);

		alerter::notify_intermittently($file, 5, "Hello this is a test");
		$this->assert(alerter::exists() === true);

		$this->assert(alerter::delete_all() === true);

		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		alerter::notify_intermittently($file, 5, "Hello this is a test");
		$this->assert(alerter::exists() === false);

		alerter::notify_intermittently($file, 5, "Hello this is a test");
		$this->assert(alerter::exists() === true);

		$this->assert(alerter::delete_all() === true);
		$this->assert(alerter::exists() === false);
		alerter::notify_intermittently($file, 1, "Hello this is a test2");
		$this->assert(alerter::exists() === true);

		$this->assert(alerter::delete_all() === true);
	}

	function test_alerter_delete_all() {
		alerter::delete_all();
	}
}
