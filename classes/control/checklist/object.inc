<?php
class Control_Checklist_Object extends Control_Checklist {
	protected $class = null;
	protected $objects = array();

	public function is_visible() {
		return count($this->objects) !== 0;
	}

	public function where(array $where = null) {
		return is_array($where) ? $this->set_option("where", $where) : $this->option_array("where");
	}
	protected function hook_options() {
		$object = Object::cache_object($this->class);
		$name_col = $object->name_column();
		$this->objects = array();
		$control_options = array();
		foreach (Object::class_query($this->class)->where($this->option_array("where"))->order_by($this->option('order_by', $name_col))->object_iterator() as $id => $object) {
			$this->objects[$id] = $object;
			$control_options[$id] = $object->member($name_col);
		}
		return $control_options;
	}

	/**
	 * (non-PHPdoc)
	 * @see Control_Options::theme_variables()
	 */
	public function theme_variables() {
		return parent::theme_variables() + array(
			"control_objects" => $this->objects
		);
	}
}