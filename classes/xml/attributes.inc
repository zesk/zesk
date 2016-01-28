<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/attributes.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:15:19 EST 2010 17:15:19
 */
class XML_Attributes extends Options {
	function __construct($attributes) {
		$opts = array();
		foreach ($attributes as $k => $v) {
			$opts[$k] = strval($v);
		}
		parent::__construct($opts);
	}
}
