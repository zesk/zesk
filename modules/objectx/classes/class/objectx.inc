<?php
use zesk\Object;
use zesk\Class_Object;

class Class_ObjectX extends zesk\Class_Object {
	
	public $target_class = null;
	
	public $has_one = array('id' => 'null');
	
	public $column_types = array('id' => self::type_object);
	
	protected function configure(Object $object) {
		$this->target_class = $object->option('objectx_class');
		
		$fields = self::_load_fields();
		
		$this->has_one['id'] = $this->target_class;
		
		foreach ($this->fields as $field) {
			
		}
	}
	
	protected function _load_fields() {
	}
}
