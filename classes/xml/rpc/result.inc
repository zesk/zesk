<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/rpc/result.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:26:15 EST 2010 17:26:15
 */

class XML_RPC_Result
{
	public $Type;
	public $MethodName;
	public $Parameters;

	function __construct()
	{
		$this->Type = false;
		$this->MethodName = false;
		$this->Parameters = array();
	}

	function isFault()
	{
		if ($this->Type == 'fault') {
			return true;
		}
		return false;
	}

	function faultError()
	{
		if (!$this->isFault()) {
			return false;
		}
		$p = $this->Parameters[0];
		return new XML_RPC_Exception(intval(avalue($p,'faultCode',-1)),avalue($p,"faultString"));
	}
}

