<?php declare(strict_types=1);
namespace zesk;

class Class_ORMX extends Class_ORM {
	public $target_class = null;

	public $has_one = [
		'id' => 'null',
	];

	public $column_types = [
		'id' => self::type_object,
	];

	protected function configure(ORM $object): void {
		$this->target_class = $object->option('objectx_class');

		$fields = $this->_load_fields();

		$this->has_one['id'] = $this->target_class;

		foreach ($this->fields as $field) {
		}
	}

	protected function _load_fields(): void {
	}
}
