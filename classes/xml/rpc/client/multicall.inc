<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/rpc/client/multicall.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:36:43 EST 2010 17:36:43
 */
class XML_RPC_Client_Multicall extends XML_RPC_Client {

	private $calls = array();

	function __construct($url) {
		parent::__construct($url);
		$this->user_agent('Zesk XML-RPC multicall v' . self::XML_RPC_VERSION);
		$this->calls = array();
	}

	function addCall() {
		$args = func_get_args();
		$methodName = array_shift($args);
		$this->calls[] = array(
			'methodName' => $methodName, 
			'params' => $args
		);
	}

	function clear() {
		$this->calls = array();
	}

	function query() {
		// Prepare multicall, then call the parent::query() method
		return parent::query('system.multicall', $this->calls);
	}
}
