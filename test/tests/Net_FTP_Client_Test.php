<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 */
class Net_FTP_Client_Test extends Test_Unit {
	public function test_basics(): void {
		$url = 'ftp://user:pass@localhost';
		$directory = null;
		$log_file = false;
		$testx = new Net_FTP_Client($this->application, $url);

		$isOn = true;
		$testx->passive($isOn);

		$this->assert(!$testx->is_connected());
	}
}
