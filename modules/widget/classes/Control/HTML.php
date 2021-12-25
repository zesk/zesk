<?php declare(strict_types=1);
/**
 * $Id: HTML.php 4380 2017-03-05 20:47:58Z kent $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 *
 * TODO KMD - Fix this to integrate with modules for plug-in Richtext Editors
 */
namespace zesk;

/**
 *
 * @package zesk
 * @subpackage control
 */
class Control_HTML extends Control_Text {
	protected function hook_construct(): void {
		$this->sanitize_html(true);
		if (!$this->has_option("sanitize_strip_tags")) {
			$this->sanitize_strip_tags(false);
		}
	}
}
