<?php
/**
 * $Id: html.inc 3581 2016-01-28 19:21:03Z kent $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 *
 * TODO KMD - Fix this to integrate with modules for plug-in Richtext Editors
 */

/**
 *
 * @package zesk
 * @subpackage control
 */
class Control_HTML extends Control_Text {
	protected function hook_construct() {
		$this->sanitize_html(true);
		if (!$this->has_option("sanitize_strip_tags")) {
			$this->sanitize_strip_tags(false);
		}
	}
}
