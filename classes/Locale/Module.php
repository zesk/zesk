<?php
/**
 * @package zesk
 * @subpackage locale
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Locale;

/**
 *
 */
use zesk\Request;
use zesk\Response_Text_HTML;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {

	/**
	 * Output our locale translation files for JavaScript to use
	 *
	 * @param \Request $request
	 * @param \zesk\Response_Text_HTML $response
	 */
	public function hook_head(Request $request, Response_Text_HTML $response) {
		$response->javascript("/share/zesk/js/locale.js", array(
			"weight" => -20,
			"share" => true
		));
		$response->javascript("/locale/js?ll=" . $this->id(), array(
			"weight" => -10,
			"is_route" => true,
			"route_expire" => 3600 /* once an hour */
		));
	}
}