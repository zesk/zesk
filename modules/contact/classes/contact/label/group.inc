<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/label/group.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class Contact_Label_Group extends Object {
	protected $columns = array(
		"ID", 
		"Name"
	);
	protected $find_keys = array(
		"Name"
	);
	
	public static function register_group($name) {
		if (empty($name)) {
			return null;
		}
		$g = new Contact_Label_Group(array(
			"Name" => $name
		));
		$g->register();
		return $g->id();
	}
}
