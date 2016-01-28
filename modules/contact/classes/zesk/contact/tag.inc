<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/zesk/contact/tag.inc $
* @package zesk
* @subpackage objects
*/

/**
 * Zesk_Contact_Tag
 * 
 * @author kent
 */
class Zesk_Contact_Tag extends Object {

	public static function register_tag($name, $user) {
		$x = new Contact_Tag(array(
			"Name" => $name, 
			"User" => $user
		));
		if (!$x->register()) {
			return null;
		}
		return $x;
	}

	public static function to_array($user, $where = null) {
		$obj = Object::class_query('Contact_Tag');
		return $obj->where('User', $user)
			->where($where)
			->order_by('Name')
			->to_array('ID');
	}
}

