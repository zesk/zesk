<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\FilePermission;
use zesk\IPv4;
use zesk\System;

/**
 * @coversDefaultClass \zesk\System
 * @uses System
 */
class SystemTest extends TestCase
{
	/**
	 * @return void
	 * @covers ::uname
	 */
	public function test_uname(): void
	{
		$this->assertGreaterThan(0, strlen(System::uname()));
	}

	/**
	 * @return void
	 * @covers ::processId
	 */
	public function test_process_id(): void
	{
		$this->assertGreaterThan(0, System::processId());
	}

	/**
	 * @return void
	 * @covers ::ipAddresses
	 */
	public function test_ip_addresses(): void
	{
		foreach (System::ipAddresses($this->application) as $ip) {
			$this->assertTrue(IPv4::valid($ip), "$ip is not a valid IPv4");
		}
	}

	/**
	 * @return void
	 * @covers ::macAddresses
	 */
	public function test_mac_addresses(): void
	{
		$macs = System::macAddresses($this->application);
		$this->assertGreaterThan(0, count($macs));
	}

	/**
	 * @return void
	 * @covers ::interfaceConfiguration
	 */
	public function testInterfaceConfiguration(): void
	{
		$interfaces = System::interfaceConfiguration($this->application);
		$this->assertIsArray($interfaces);
		$this->assertGreaterThan(0, count($interfaces));
	}

	/**
	 * @return void
	 * @throws FilePermission
	 * @covers ::loadAverages
	 */
	public function test_load_averages(): void
	{
		$averages = System::loadAverages();
		$this->assertIsArray($averages);
		$this->assertArrayHasKeys([
			0,
			1,
			2,
		], $averages);
	}

	/**
	 * @return void
	 * @covers ::distro
	 */
	public function test_distro(): void
	{
		$result = System::distro();
		$this->assertArrayHasKeys([
			'system',
			'release',
			'arch',
			'brand',
			'distro',
			'source',
		], $result);
		$this->assertEquals('Linux', $result['system']);
		$this->assertEquals('Debian', $result['brand']);
		$this->assertEquals('/etc/debian_version', $result['source']);
	}

	/**
	 * @return void
	 * @covers ::memoryLimit
	 */
	public function test_memory_limit(): void
	{
		$this->assertIsInteger(System::memoryLimit());
	}

	/**
	 * @return void
	 * @covers ::volumeInfo
	 */
	public function test_volume_info(): void
	{
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
