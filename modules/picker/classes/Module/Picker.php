<?php
/**
 * @package zesk
 * @subpackage picker
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Picker extends Module_JSLib {
	protected $javascript_paths = array(
		'/share/picker/js/picker.js'
	);
	protected $css_paths = array(
		'/share/picker/css/picker.css'
	);
	function hook_cron() {
		$this->application->locale->__("No matches found for search &ldquo;{q}&rdquo;.");
	}
}
