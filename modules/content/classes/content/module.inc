<?php
/**
 * $URL$
 * @package zesk
 * @subpackage content
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */

class Content_Module extends zesk\Module {
	function hook_application_classes() {
		return to_list("Content_File;Content_Image;Content_Image;Content_Link;Content_Menu;Content_Video");
	}
}

