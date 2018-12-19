<?php
namespace zesk;

class Class_ORMX extends Class_ORM {
	public $target_class = null;

	public $has_one = array(
		'id' => 'null',
	);

	public $column_types = array(
		'id' => self::type_object,
	);

	protected function configure(ORM $object) {
		$this->target_class = $object->option('objectx_class');

		$fields = self::_load_fields();

		$this->has_one['id'] = $this->target_class;

		foreach ($this->fields as $field) {
		}
	}

	protected function _load_fields() {
	}
}
