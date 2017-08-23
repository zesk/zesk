<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/exception.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:21:37 EST 2010 17:21:37
 */
namespace xmlrpc;

use zesk\HTML;

/**
 * XML_RPC_Exception
 *
 * @package zesk
 * @subpackage system
 */
class Exception extends \zesk\Exception {
	const ERROR_PARSE_NOT_WELL_FORMED = -32700;
	const ERROR_PARSE_UNSUPPORTED_ENCODING = -32701;
	const ERROR_PARSE_INVALID_CHARACTER = -32702;
	const ERROR_SERVER_INVALID_XML_RPC = -32600;
	const ERROR_SERVER_METHOD_NOT_FOUND = -32601;
	const ERROR_SERVER_METHOD_PARAMETERS = -32602;
	const ERROR_SERVER_INTERNAL = -32603;
	const ERROR_APPLICATION = -32500;
	const ERROR_SYSTEM = -32400;
	const ERROR_TRANSPORT = -32300;
	static $errorCodes = array(
		self::ERROR_PARSE_NOT_WELL_FORMED => "Parse error. not well formed",
		self::ERROR_PARSE_UNSUPPORTED_ENCODING => "Parse error. unsupported encoding",
		self::ERROR_PARSE_INVALID_CHARACTER => "Parse error. invalid character for encoding",
		self::ERROR_SERVER_INVALID_XML_RPC => "Server error. invalid xml-rpc. not conforming to spec.",
		self::ERROR_SERVER_METHOD_NOT_FOUND => "Server error. requested method not found",
		self::ERROR_SERVER_METHOD_PARAMETERS => "Server error. invalid method parameters",
		self::ERROR_SERVER_INTERNAL => "Server error. internal xml-rpc error",
		self::ERROR_APPLICATION => "Application error",
		self::ERROR_SYSTEM => "System error",
		self::ERROR_TRANSPORT => "Transport error"
	);
	
	/**
	 * Error code
	 * 
	 * @var numeric
	 */
	public $code = null;
	
	/**
	 * Error message
	 * 
	 * @var string
	 */
	public $message = null;
	
	/**
	 * 
	 * @param integer $code
	 * @param string $message
	 * @param array $arguments
	 */
	function __construct($code, $message, array $arguments = array()) {
		$code = intval($code);
		$this->code = $code;
		$this->message = $message ? $message : avalue(self::$errorCodes, $code, "No error description");
		parent::__construct($message, $arguments, $code);
	}
	private function _member_xml($n, $t, $v) {
		return "<member><name>$n</name><value><$t>$v</$t></value></member>";
	}
	function toXML() {
		return HTML::tag("methodResponse", HTML::tag("fault", HTML::tag("value", null, HTML::tag("struct", self::_member_xml("faultCode", "int", $this->code) . self::_member_xml("faultString", "string", $this->message)))));
	}
}

