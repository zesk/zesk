<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Net\FTP;

use testZesk\TestCase;
use zesk\Exception\SyntaxException;
use zesk\Net\FTP\Client;

/**
 * @coversDefaultClass \zesk\Net\FTP\Client
 */
class ClientTest extends TestCase
{
	/**
	 * @return void
	 * @throws SyntaxException
	 * @covers ::setPassive ::passive
	 */
	public function test_basics(): void
	{
		$url = 'ftp://user:pass@localhost';

		$testObject = new Client($this->application, $url);

		foreach ([
			true,
			false,
		] as $isOn) {
			$this->assertEquals($isOn, $testObject->setPassive($isOn)->passive());
		}

		$this->assertFalse($testObject->isConnected());
	}
}
