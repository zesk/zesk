<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/rpc/value/binary.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:24:56 EST 2010 17:24:56
 */

class XML_RPC_Value_Binary extends XML_RPC_Value
{
	function __construct($data, $isXML=false)
	{
		if ($isXML) {
			$data = base64_decode($data);
		}
		parent::__construct($data, 'base64');
	}
	function toXML()
	{
		return base64_encode($this->Value);
	}
	function fromXML($value)
	{
		$this->Value = base64_decode($value);
	}
}
