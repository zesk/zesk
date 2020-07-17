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
class Control_Select_ORM_Available extends Control_Select_ORM {
	/**
	 *
	 * {@inheritDoc}
	 * @see Control_Select_ORM::hook_options()
	 */
	protected function hook_options() {
		$sql = $this->class_object->database()->sql();

		$column = $this->query_column();
		$column = StringTools::right($column, ".", $column);

		$column = $sql->unquote_column($column);

		$query = $this->application->orm_registry($this->class)->query_select();
		$query->what("id", $column);
		$query->distinct(true);
		$query->order_by($this->option('order_by', $column));
		$query->where($this->_where());
		$query->where("$column|!=", "");
		$result = $query->to_array("id", "id");
		return ArrayTools::capitalize(array_change_key_case($result));
	}
}
