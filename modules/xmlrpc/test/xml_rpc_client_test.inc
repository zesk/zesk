<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/xml_rpc_client_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class XML_RPC_Client_Test extends Test_Unit {
	function test_main() {
		$url = $this->option("xmlrpc_test_url");
		$x = new XML_RPC_Client($url);
		
		$x->setCallMap(array(
			"__" => "."
		));
		
		//$x->set_option("debug", true);
		$x->query("system.listmethods");
		
		$x->system__listmethods();
		
		$x->isFault();
		
		XML_RPC_Client::defaultUserAgent();
		
		$default = false;
		$x->userAgent($default);
		
		$value = null;
		$x->setUserAgent($value);
		
		$x->isPost();
		
		$x->url();
		
		$x->headers();
		
		$x->responseCode();
		
		$x->responseCodeType();
		
		$x->responseMessage();
		
		$x->responseProtocol();
		
		$x->hasHeaders();
		
		$name = "Content-Length";
		$default = false;
		$x->getHeader($name, $default);
		
		$x->go();
		
		$url = null;
		XML_RPC_Client::simpleGet($url);
		
		$x->domain();
		
		echo basename(__FILE__) . ": success\n";
	}
}
