<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

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

		$column = $this->queryColumn();
		$column = StringTools::right($column, '.', $column);

		$column = $sql->unquoteColumn($column);

		$query = $this->application->ormRegistry($this->class)->querySelect();
		$query->addWhat('id', $column);
		$query->setDistinct(true);
		$query->setOrderBy($this->option('order_by', $column));
		$query->appendWhere($this->_where());
		$query->addWhere("$column|!=", '');
		$result = $query->toArray('id', 'id');
		return ArrayTools::capitalize(array_change_key_case($result));
	}
}
