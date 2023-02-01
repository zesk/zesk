<?php declare(strict_types=1);
namespace zesk;

class System_Test extends UnitTest {
	public function test_host_id(): void {
		System::hostId();
	}

	public function test_uname(): void {
		System::uname();
	}

	public function test_process_id(): void {
		System::processId();
	}

	public function test_ip_addresses(): void {
		System::ipAddresses($this->application);
	}

	public function test_mac_addresses(): void {
		System::macAddresses($this->application);
	}

	public function test_ifconfig(): void {
		$interfaces = System::ifconfig($this->application);
		$this->assertIsArray($interfaces);
	}

	public function test_load_averages(): void {
		$default = null;
		System::loadAverages($default);
	}

	public function test_distro(): void {
		$default = null;
		System::distro();
	}

	public function test_memory_limit(): void {
		$this->assertIsInteger(System::memoryLimit());
	}

	public function test_volume_info(): void {
		$info = System::volumeInfo();
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));
		assert(array_key_exists('path', $slash));

		$info = System::volumeInfo('/');
		assert(array_key_exists('/', $info));
		$slash = $info['/'];
		assert(array_key_exists('filesystem', $slash));
		assert(array_key_exists('total', $slash));
		assert(array_key_exists('used', $slash));
		assert(array_key_exists('free', $slash));

		$info = System::volumeInfo('/not-a-volume');
		assert(is_array($info));
		assert(count($info) === 0);
	}
}
