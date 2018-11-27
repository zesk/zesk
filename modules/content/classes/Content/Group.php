<?php
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_zesk\Content_Group
 * @author kent
 *
 */
abstract class Content_Group extends ORM {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORM::store()
	 */
	public function store() {
		if (empty($this->CodeName)) {
			$this->CodeName = $this->clean_code_name($this->Name);
		}
		return parent::store();
	}

	/**
	 *
	 * @param unknown $mixed
	 * @param unknown $options
	 * @return \zesk\ORM
	 */
	public function group_object($mixed = null, $options = null) {
		return $this->orm_factory($this->group_class, $mixed, $options);
	}

	protected function order_methods() {
		return array(
			"name" => "Order by name",
			"order" => "Order explicitly",
			"created" => "Order by creation date",
		);
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 * @return \zesk\Database_Query_Select|boolean
	 */
	public function hook_query_alter(Database_Query_Select $query) {
		$alias = $query->alias();
		switch ($this->OrderMethod) {
			case "name":
				$object = ORM::cache_object($this->group_class);
				return $query->order_by("$alias." . $object->name_column());
			case "order":
				return $query->order_by("$alias.OrderIndex");
			case "created":
				return $query->order_by("$alias.Created");
			default:
				return false;
		}
	}
}
