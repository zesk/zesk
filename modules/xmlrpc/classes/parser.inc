<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/parser.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:26:58 EST 2010 17:26:58
 */
namespace xmlrpc;

use zesk\Timestamp;

/**
 * 
 * @author kent
 *
 */
class Parser extends \SimpleXMLElement {
	function parse() {
		$match = false;
		$xml = $this->asXML();
		if (!preg_match("/<([A-Za-z][A-Za-z0-9:]+)/", $xml, $match)) {
			throw new Exception("No XML tag");
		}
		$k = $match[1];
		$result = new Result();
		
		switch (strtolower($k)) {
			case "methodresponse":
				foreach ($this->fault as $fault) {
					$result->Type = "fault";
					$result->Parameters[] = $fault->value->parseType();
					return $result;
				}
				$result->Type = "methodResponse";
				$param = $this->params->param;
				$result->Parameters[] = $param->parseType();
				return $result;
			case "methodcall":
				$result->Type = "methodCall";
				$result->MethodName = strval($this->methodName);
				foreach ($this->params->param as $param) {
					$result->Parameters[] = $param->parseType();
				}
				return $result;
		}
		throw new Exception("Unknown XML methodResponse or methodCall: $k");
	}
	function parseType() {
		foreach ($this->children() as $tag => $value) {
			switch ($tag) {
				case 'int':
				case 'i4':
					return intval($value);
				case 'double':
					return doubleval($value);
				case 'value': // No type indicated is a string.
					if ($value instanceof Parser) {
						return $value->parseType();
					} else {
						return strval($value);
					}
				case 'string':
					return strval($value);
				case 'dateTime.iso8601':
					$dt = new Timestamp();
					if (!$dt->iso8601(strval($value))) {
						throw new Exception("Invalid ISO 8601 date format: $value");
					}
					return $dt;
				case 'boolean':
					return to_bool(trim(strval($value)));
				case 'base64':
					$b = new Value_Binary($value, true);
					return $b;
				case 'array':
					foreach ($value->data as $v) {
						$result = array();
						foreach ($v->value as $vv) {
							$result[] = $vv->parseType();
						}
						return $result;
					}
					break;
				case 'data':
					$result = array();
					foreach ($v->value as $vv) {
						$result[] = $vv->parseType();
					}
					return $result;
				case 'struct':
					$result = array();
					$name = false;
					foreach ($value->member as $v) {
						$name = strval($v->name);
						$vv = $v->value;
						$result[$name] = $vv->parseType();
					}
					return $result;
				default :
					throw new Exception(Exception::ERROR_SERVER_INVALID_XML_RPC, "Parser::unhandled tag $tag => $value");
			}
		}
		return false;
	}
}

