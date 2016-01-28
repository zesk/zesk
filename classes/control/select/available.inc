<?php

class Control_Select_Available extends Control_Select {

	public function what_columns($column_id = null, $column_name = null) {
		if ($column_id === null) {
			$column_id = $this->option("column_id");
			$column_name = $this->option("column_name", $column_id);
			return array(
				$column_id,
				$column_name
			);
		}
		$this->set_option("column_id", $column_id);
		if ($column_name) {
			$this->set_option("column_name", $column_name);
		}
		return $this;
	}

	/**
	 * @var Control_List
	 */
	private $list_parent = false;

	private function _list_parent() {
		if ($this->list_parent === false) {
			$parent = $this;
			do {
				$parent = $parent->parent();
				if ($parent instanceof Control_List) {
					$this->list_parent = $parent;
					return $parent;
				}
			} while ($parent !== null);
		}
		$this->list_parent = $parent;
		return $this->list_parent;
	}

	protected function hook_options() {
		return array();
	}

	protected function hook_initialized() {
		$parent = $this->_list_parent();
		/* @var $query Database_Query_Select */
		$query = $parent->query()->duplicate();
		$query->where(false);
		$query->limit(0,-1);
		$query->distinct(true);
		list($column_id, $column_name) = $this->what_columns();
		$query->what(array(
			"id" => $column_id,
			"name" => $column_name
		));
		$query->where("$column_name|!=", "");
		$this->control_options(arr::capitalize(array_change_key_case($query->to_array("id", "name"))));
	}
}