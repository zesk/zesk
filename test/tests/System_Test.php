<?php
namespace zesk;

class System_Test extends Test_Unit {
	function test_host_id() {
		System::host_id();
	}
	function test_uname() {
		System::uname();
	}
	function test_process_id() {
		System::process_id();
	}
	function test_ip_addresses() {
		System::ip_addresses($this->application);
	}
	function test_mac_addresses() {
		System::mac_addresses($this->application);
	}
	function test_ifconfig() {
		$ifconfig = System::ifconfig($this->application);
	}
	function test_load_averages() {
		$default = null;
		System::load_averages($default);
	}
	function test_distro() {
		$default = null;
		$this->log(_dump(System::distro()));
	}
	function test_memory_limit() {
		$this->assert_is_integer(System::memory_limit());
	}
	function test_volume_info() {
		$info = System::volume_info();
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));
		assert(array_key_exists('path', $slash));
		
		$info = System::volume_info('/');
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));
		
		$info = System::volume_info('/not-a-volume');
		assert(is_array($info));
		assert(count($info) === 0);
	}
}
