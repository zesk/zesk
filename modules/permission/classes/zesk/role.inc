<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/permission/classes/zesk/role.inc $
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 4:58 PM
 */
namespace zesk;

/**
 * @see Class_Zesk_Role
 * @author kent
 * @todo move to zesk\Role
 */
abstract class Role extends Object {
	static function root_id() {
		return Object::class_query('Role')->what('id')->where('is_root', true)->integer('id', null);
	}
	static function default_id() {
		return Object::class_query('Role')->what('id')->where('is_default', true)->integer('id', null);
	}
	function is_root() {
		return $this->member_boolean("is_root");
	}
	function is_default() {
		return $this->member_boolean("is_default");
	}
}

