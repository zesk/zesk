<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/label/group.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Label_Group extends ORM {
	public static function register_group($name) {
		if (empty($name)) {
			return null;
		}
		$g = new self(array(
			"Name" => $name
		));
		$g->register();
		return $g->id();
	}
}
