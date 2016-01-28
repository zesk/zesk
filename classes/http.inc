<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/http.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

/**
 * @deprecated 2013 - Remove
 * @author kent
 *
 */
class http {

	/**
	 * TODO Move this to Response
	 */
	public static function p3p($policyref, $compact_p3p = null) {
		zesk::deprecated();
		$response = Response::instance();
		if (empty($compact_p3p)) {
			$compact_p3p = "NOI DSP NID PSA ADM OUR IND NAV COM";
		}
		if (strpos($compact_p3p, 'CP=') === false) {
			$compact_p3p = "CP=\"$compact_p3p\"";
		}
		$response->header("P3P", "policyref=\"$policyref\", $compact_p3p");
	}
	public static function tld($server) {
		$x = explode(".", strrev($server), 3);
		while (count($x) >= 3) {
			array_pop($x);
		}
		return "." . strrev(implode(".", $x));
	}
}
