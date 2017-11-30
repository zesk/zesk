<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/value.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:23:39 EST 2010 17:23:39
 */
namespace xmlrpc;

use zesk\Timestamp;

/**
 * XML_RPC_Value
 *
 * Abstract object representing XML RPC Value for serialization and parsing
 *
 * @package zesk
 * @subpackage system
 */
class Value extends Base {
	public $Type;
	public $Value;
	function __construct($data, $type = false) {
		$this->Value = $data;
		$this->Type = ($type) ? $type : $this->computeType();
		
		if ($type == 'struct' || $type == 'array') {
			foreach ($this->Value as $k => $v) {
				if (!$v instanceof self) {
					$this->Value[$k] = new self($v);
				}
			}
		}
	}
	private function computeType() {
		$value = $this->Value;
		if (is_bool($value)) {
			return self::type_boolean;
		}
		if (is_integer($value)) {
			return self::type_integer;
		}
		if (is_double($value)) {
			return self::type_real;
		}
		if (is_array($value)) {
			return self::isStruct($value) ? self::type_object : self::type_array;
		}
		if (!is_object($value)) {
			return self::type_string;
		}
		if ($value instanceof Timestamp) {
			return self::type_date;
		}
		if ($value instanceof Value_Binary) {
			return self::type_binary;
		}
		if (method_exists($value, "toXMLRPC")) {
			$this->Value = $value->toXMLRPC();
		} else {
			$this->Value = get_object_vars($value);
		}
		return 'struct';
	}
	public function toXML() {
		$tag = $this->Type;
		$result[] = "<$tag>";
		switch (strtolower($tag)) {
			case self::type_boolean:
				$result[] = intval($this->Value);
				break;
			case self::type_integer:
			case self::type_real:
				$result[] = $this->Value;
				break;
			case self::type_string:
				$result[] = htmlspecialchars($this->Value);
				break;
			case self::type_array:
				$result[] = "<data>\n";
				foreach ($this->Value as $item) {
					$item = new self($item);
					$result[] = '<value>' . $item->toXML() . "</value>\n";
				}
				$result[] = '</data>';
				break;
			case self::type_object:
				foreach ($this->Value as $k => $v) {
					if (!$v instanceof self) {
						$v = new self($v);
					}
					$result[] = "<member><name>$k</name><value>" . $v->toXML() . "</value></member>\n";
					;
				}
				break;
			case self::type_date:
				assert('$this->Value instanceof Timestamp');
				$result[] = $this->Value->toISO8601();
				break;
			case self::type_binary:
				assert('$this->Value instanceof XML_RPC_Value_Binary');
				$result[] = $this->Value->toXML();
				break;
			default :
				throw new Exception("XML_RPC_Value::toXML() Invalid type {type}", array(
					"type" => $this->Type
				), Exception::ERROR_APPLICATION);
		}
		$result[] = "</$tag>";
		return implode("", $result);
	}
	private static function isStruct($x) {
		$i = 0;
		foreach (array_keys($x) as $k) {
			if (strval($k) !== strval($i)) {
				return true;
			}
			$i++;
		}
		return false;
	}
}
