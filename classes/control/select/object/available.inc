<?php

class Control_Select_Object_Available extends Control_Select_Object {

	protected function hook_options() {
		// TODO Cache this
		$db = $this->class_object->database();
		$sql = $db->sql();

		$column = $this->query_column();
		$column = str::right($column, ".", $column);

		$column = $sql->unquote_column($column);

		$query = Object::class_query($this->class);
		$query->what("id", $column);
		$query->distinct(true);
		$query->order_by($this->option('order_by', $column));
		$query->where($this->_where());
		$query->where("$column|!=", "");
		return arr::capitalize(array_change_key_case($query->to_array("id", "id")));
	}
}