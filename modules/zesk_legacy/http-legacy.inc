<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/zesk_legacy/http-legacy.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

function request_uri($asHREF = false, $rooted = false)
{
	$qs = avalue($_SERVER, "QUERY_STRING", "");
	if ($asHREF) {
		$qs = str_replace("&", "&amp;", $qs);
	}
	return url::current_uri();
}

