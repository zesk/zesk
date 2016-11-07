<?php

/**
 * @see Class_County
 * @author kent
 *
 */
class County extends Object {
	public function reassign($new) {
		$old_id = intval($this->id);
		$new_id = empty($new) ? null : Object::mixed_to_id($new);
		Object::class_query_update('Contact_Address')->ignore_constraints(true)->value("county", $new_id)->where("county", $old_id)->execute();
	}

	public function usage_statistics() {
		return array(
			'Contact_Address' => Object::class_query('Contact_Address')->where("county", $this->id)->what("*total", "COUNT(X.id)")->one_integer("total")
		);
	}

	public static function permissions() {
		return parent::default_permissions(__CLASS__);
	}

}
