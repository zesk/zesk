<?php
class test_cluster extends Test_Unit {

	function test_hosts() {
		$match = null;
		cluster::hosts($match);

		$test_hosts = $this->test_sandbox("hosts");

		$data = <<<EOF

# NOTHING

192.168.0.1			host1			# WEB_SERVER LIVE_HOST
192.168.0.2			host2			# WEB_SERVER LIVE_HOST
192.168.0.3			host3 host33	# WEB2_SERVER LIVE_HOST
192.168.0.4			host4 			# WEB2_SERVER LIVE_HOST DUDE

				#NOTHING
				              #NOTHING
       #NOTHING
# NOTHING

EOF;
		file_put_contents($test_hosts, $data);

		$this->assert_arrays_equal(cluster::hosts("NOTHING", $test_hosts), array(), "No matches");
		$this->assert_arrays_equal(cluster::hosts("DUDE", $test_hosts), array(
			'192.168.0.4' => 'host4'
		), "1 match: DUDE");
		$this->assert_arrays_equal(cluster::hosts("WEB2_SERVER;DUDE", $test_hosts), array(
			'192.168.0.4' => 'host4'
		), "2 matches");
		$this->assert_arrays_equal(cluster::hosts("WEB_SERVER;DUDE", $test_hosts), array(), "No matches");
		$this->assert_arrays_equal(cluster::hosts("LIVE_HOST", $test_hosts), array(
			'192.168.0.1' => 'host1',
			'192.168.0.2' => 'host2',
			'192.168.0.3' => 'host3',
			'192.168.0.4' => 'host4'
		), "LIVE_HOST");
		$this->assert_arrays_equal(cluster::hosts("_SERVER", $test_hosts), array(
			'192.168.0.1' => 'host1',
			'192.168.0.2' => 'host2',
			'192.168.0.3' => 'host3',
			'192.168.0.4' => 'host4'
		), "_SERVER");
		$this->assert_arrays_equal(cluster::hosts("2_SERVER;LIVE", $test_hosts), array(
			'192.168.0.3' => 'host3',
			'192.168.0.4' => 'host4'
		), "_SERVER");
	}

	function test_command() {
		$query = null;
		$from_host = null;
		$include_me = true;
		cluster::command($query, $from_host, $include_me);
	}

	function test_database_master() {
		cluster::database_master();
	}

	function test_database_slaves() {
		cluster::database_slaves();
	}

	function test_host_to_internal_name() {
		$h = null;
		cluster::host_to_internal_name($h);
	}

	function test_host_to_short_name() {
		$h = null;
		cluster::host_to_short_name($h);
	}

	function test_hosts_file() {
		cluster::hosts_file();
	}

	function test_hosts_trusted() {
		cluster::hosts_trusted();
	}

	function test_hosts_web() {
		cluster::hosts_web();
	}

	function test_is_database_master() {
		cluster::is_database_master();
	}

	function test_is_database_slave() {
		cluster::is_database_slave();
	}

	function test_is_me() {
		$host_id = null;
		cluster::is_me($host_id);
	}

	function test_is_web_master() {
		cluster::is_web_master();
	}

	function test_resolver_domain() {
		cluster::resolver_domain();
	}

	function test_service_restart() {
		//$service = null;
		//cluster::service_restart("noservice"); TODO
	}

	function test_service_sync_files() {
		$on_off = null;
		cluster::service_sync_files($on_off);
	}

	function test_unlink() {
		$f = null;
		cluster::unlink($f);
	}

	function test_web_master() {
		cluster::web_master();
	}
}
