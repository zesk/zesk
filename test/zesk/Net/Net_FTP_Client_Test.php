<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace test\zesk\Net;

use zesk\Net\FTP\Client as FTPClient;
use zesk\UnitTest;

/**
 *
 */
class Net_FTP_Client_Test extends UnitTest
{
	public function test_basics(): void
	{
		$url = 'ftp://user:pass@localhost';

		$testObject = new FTPClient($this->application, $url);

		foreach ([
			true,
			false,
		] as $isOn) {
			$this->assertEquals($isOn, $testObject->setPassive($isOn)->passive());
		}

		$this->assertFalse($testObject->isConnected());
	}
}
