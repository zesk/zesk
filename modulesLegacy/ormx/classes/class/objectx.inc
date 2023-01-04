<?php declare(strict_types=1);
namespace zesk;

class Class_BaseX extends Class_Base {
	public $target_class = null;

	public array $has_one = [
		'id' => 'null',
	];

	public array $column_types = [
		'id' => self::TYPE_OBJECT,
	];

	protected function configure(ORMBase $object): void {
		$this->target_class = $object->option('objectx_class');

		$fields = $this->_load_fields();

		$this->has_one['id'] = $this->target_class;

		foreach ($this->fields as $field) {
		}
	}

	protected function _load_fields(): void {
	}
}
