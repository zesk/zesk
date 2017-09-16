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
class Control_Search extends Control_Text {
	protected $search_columns = array();
	protected $options = array();
	function search_columns($set = null) {
		if ($set !== null) {
			$this->search_columns = to_list($set);
			return $this;
		}
		return $this->search_columns;
	}
	protected function defaults() {
		$this->value($this->request->get($this->name()));
	}
	function hook_query(Database_Query_Select $query) {
		$value = $this->value();
		if ($value === "" || $value === null) {
			return;
		}
		$search_values = trim($value);
		$search_values = explode(' ', preg_replace('/\s+/', ' ', $search_values));
		$sql = $query->sql();
		$alias = $query->class_alias();
		$where = array();
		foreach ($search_values as $search_value) {
			$value_where = array();
			foreach ($this->search_columns as $col) {
				$value_where[$col . "|%"] = $search_value;
			}
			$where[] = $value_where;
		}
		$query->where($where);
		$query->condition(__("match the string \"{q}\"", array(
			"q" => $value
		)));
	}
	function theme_variables() {
		return parent::theme_variables() + array(
			'search_title' => $this->option('search_title')
		);
	}
}
