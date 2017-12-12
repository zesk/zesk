<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Checklist_Object extends Control_Checklist {
	protected $class = null;
	protected $objects = array();
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::is_visible()
	 */
	public function is_visible() {
		return count($this->objects) !== 0;
	}
	
	/**
	 * 
	 * @param array $where
	 */
	public function where(array $where = null) {
		return is_array($where) ? $this->set_option("where", $where) : $this->option_array("where");
	}
	
	/**
	 * 
	 * @return mixed[]|array[]
	 */
	protected function hook_options() {
		$object = $this->application->object($this->class);
		$name_col = $object->name_column();
		$this->objects = array();
		$control_options = array();
		$query = $this->application->query_select($this->class);
		$query->where($this->option_array("where"));
		$query->order_by($this->option('order_by', $name_col));
		$this->call_hook("options_query", $query);
		$iterator = $query->object_iterator();
		foreach ($iterator as $id => $object) {
			$this->objects[$id] = $object;
			$control_options[$id] = $this->object_format_option_label($object);
		}
		return $control_options;
	}
	protected function object_format_option_label(ORM $object) {
		return $object->member($object->name_column());
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
