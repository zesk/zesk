<?php
/**
 * @desc       
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/classes/test/xmlrpc/server.inc $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */
class Test_XMLRPC_Server extends XML_RPC_Server {
	function __construct($methods = null) {
		parent::__construct($methods);
		$this->registerMethod("capitalize", "string", "this:capitalize", array(
			"string" => "string"
		), "Capitalizes a word", array(
			"string" => "String to capitalize"
		));
	}
	function rpc_capitalize($string) {
		return str::capitalize($string);
	}
}
