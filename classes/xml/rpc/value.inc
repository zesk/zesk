<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/rpc/value.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:23:39 EST 2010 17:23:39
 */

use zesk\Timestamp;

/**
 * XML_RPC_Value
 *
 * Abstract object representing XML RPC Value for serialization and parsing
 *
 * @package zesk
 * @subpackage system
 */
class XML_RPC_Value {
	public $Type;
	public $Value;

	function __construct($data, $type = false) {
		$this->Value = $data;
		$this->Type = ($type) ? $type : $this->computeType();

		if ($type == 'struct' || $type == 'array') {
			foreach ($this->Value as $k => $v) {
				if (!$v instanceof XML_RPC_Value) {
					$this->Value[$k] = new XML_RPC_Value($v);
				}
			}
		}
	}

	private function computeType() {
		$value = $this->Value;
		if (is_bool($value)) {
			return XML_RPC::type_boolean;
		}
		if (is_integer($value)) {
			return XML_RPC::type_integer;
		}
		if (is_double($value)) {
			return XML_RPC::type_real;
		}
		if (is_array($value)) {
			return self::isStruct($value) ? XML_RPC::type_object : XML_RPC::type_array;
		}
		if (!is_object($value)) {
			return XML_RPC::type_string;
		}
		if ($value instanceof Timestamp) {
			return XML_RPC::type_date;
		}
		if ($value instanceof XML_RPC_Value_Binary) {
			return XML_RPC::type_binary;
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
			case XML_RPC::type_boolean:
				$result[] = intval($this->Value);
				break;
			case XML_RPC::type_integer:
			case XML_RPC::type_real:
				$result[] = $this->Value;
				break;
			case XML_RPC::type_string:
				$result[] = htmlspecialchars($this->Value);
				break;
			case XML_RPC::type_array:
				$result[] = "<data>\n";
				foreach ($this->Value as $item) {
					$item = new XML_RPC_Value($item);
					$result[] = '<value>' . $item->toXML() . "</value>\n";
				}
				$result[] = '</data>';
				break;
			case XML_RPC::type_object:
				foreach ($this->Value as $k => $v) {
					if (!$v instanceof XML_RPC_Value) {
						$v = new XML_RPC_Value($v);
					}
					$result[] = "<member><name>$k</name><value>" . $v->toXML() . "</value></member>\n";
					;
				}
				break;
			case XML_RPC::type_date:
				assert('$this->Value instanceof Timestamp');
				$result[] = $this->Value->toISO8601();
				break;
			case XML_RPC::type_binary:
				assert('$this->Value instanceof XML_RPC_Value_Binary');
				$result[] = $this->Value->toXML();
				break;
			default:
				throw new XML_RPC_Exception("XML_RPC_Value::toXML() Invalid type " . $this->Type);
				return false;
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
