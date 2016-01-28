<?php
/**
 * @desc       $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/writer/interface.inc $
 * @package    fooofooo
 * @subpackage system
 * @author     kent
 * @copyright  Copyright &copy; 2009, Market Acumen, Inc.
 *             Created on Fri Feb 26 17:17:17 EST 2010 17:17:17
 */

interface XML_Writer_Interface
{
	function push($tag, $attributes=false, $content=false);
	function pop($tag=false);
	function add($tag, $attributes=false, $content=false);
	function addContent($content="");
	function output($options=false);
}

