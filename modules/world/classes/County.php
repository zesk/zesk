<?php

/**
 * @see Class_County
 * @author kent
 *
 */
namespace zesk;

class County extends Object {
	public function reassign($new) {
		$old_id = intval($this->id);
		$new_id = empty($new) ? null : Object::mixed_to_id($new);
		$this->application->query_update('Contact_Address')
			->ignore_constraints(true)
			->value("county", $new_id)
			->where("county", $old_id)
			->execute();
	}
	public function usage_statistics() {
		return array(
			'Contact_Address' => $this->application->query_select('Contact_Address')
				->where("county", $this->id)
				->what("*total", "COUNT(X.id)")
				->one_integer("total")
		);
	}
	public static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__);
	}
}
