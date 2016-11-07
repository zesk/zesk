<?php
/**
 * 
 */
use zesk\arr;
use zesk\str;

/**
 * 
 * @author kent
 *
 */
class Control_Select_Object_Available extends Control_Select_Object {
	/**
	 * 
	 * {@inheritDoc}
	 * @see Control_Select_Object::hook_options()
	 */
	protected function hook_options() {
		$sql = $this->class_object->database()->sql();
		
		$column = $this->query_column();
		$column = str::right($column, ".", $column);
		
		$column = $sql->unquote_column($column);
		
		$query = $this->application->query_select($this->class);
		$query->what("id", $column);
		$query->distinct(true);
		$query->order_by($this->option('order_by', $column));
		$query->where($this->_where());
		$query->where("$column|!=", "");
		return arr::capitalize(array_change_key_case($query->to_array("id", "id")));
	}
}
