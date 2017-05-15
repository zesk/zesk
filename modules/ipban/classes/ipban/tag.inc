<?php
use zesk\Object;

/**
 * @see Class_IPBan_Tag
 * @author kent
 */
class IPBan_Tag extends IPBan {

	public static function instance($type, $value) {
		return Object::factory(__CLASS__, array(
			"type" => IPBan_Tag_Type::instance($type),
			"value" => $value
		))->register();
	}
}
