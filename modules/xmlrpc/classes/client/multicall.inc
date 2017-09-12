<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/client/multicall.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:36:43 EST 2010 17:36:43
 */
namespace xmlrpc;

use zesk\Application;

class Client_Multicall extends Client {
	private $calls = array();
	function __construct(Application $application, $url) {
		parent::__construct($application, $url);
		$this->user_agent('Zesk XML-RPC multicall v' . self::VERSION);
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
