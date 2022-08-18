<?php declare(strict_types=1);
namespace zesk;

class System_Test extends UnitTest {
	public function test_host_id(): void {
		System::host_id();
	}

	public function test_uname(): void {
		System::uname();
	}

	public function test_process_id(): void {
		System::process_id();
	}

	public function test_ip_addresses(): void {
		System::ip_addresses($this->application);
	}

	public function test_mac_addresses(): void {
		System::mac_addresses($this->application);
	}

	public function test_ifconfig(): void {
		$ifconfig = System::ifconfig($this->application);
	}

	public function test_load_averages(): void {
		$default = null;
		System::load_averages($default);
	}

	public function test_distro(): void {
		$default = null;
		$this->log(_dump(System::distro()));
	}

	public function test_memory_limit(): void {
		$this->assert_is_integer(System::memory_limit());
	}

	public function test_volume_info(): void {
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
