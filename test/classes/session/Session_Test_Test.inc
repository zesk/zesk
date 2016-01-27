<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/session/Session_Test_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Test_Session_Mock extends Test_Unit {

	function test_main() {
		$testx = new Session_Mock();

		Session_Mock::instance();

		$testx->id();

		$name = null;
		$default = null;
		$testx->get($name, $default);

		$name = null;
		$testx->__get($name);

		$name = null;
		$value = null;
		$testx->__set($name, $value);

		$list = null;
		$testx->filter($list);

		$testx->user_id();

		$id = null;
		$ip = false;
		$testx->authenticate($id, $ip);

		$testx->deauthenticate();

		$testx->variables();

		$testx->delete();

		echo basename(__FILE__) . ": success\n";
	}
}
