<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Class_zesk\Content_Group
 * @author kent
 *
 */
abstract class Content_Group extends ORMBase {
	/**
	 *
	 * {@inheritDoc}
	 * @see ORMBase::store()
	 */
	public function store(): self {
		if (empty($this->CodeName)) {
			$this->CodeName = $this->clean_code_name($this->Name);
		}
		return parent::store();
	}

	/**
	 *
	 * @param unknown $mixed
	 * @param unknown $options
	 * @return ORMBase
	 */
	public function group_object($mixed = null, $options = null) {
		return $this->ormFactory($this->group_class, $mixed, $options);
	}

	protected function order_methods() {
		return [
			'name' => 'Order by name',
			'order' => 'Order explicitly',
			'created' => 'Order by creation date',
		];
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 * @return \zesk\Database_Query_Select|boolean
	 */
	public function hook_query_alter(Database_Query_Select $query) {
		$alias = $query->alias();
		switch ($this->OrderMethod) {
			case 'name':
				$object = ORMBase::cache_object($this->group_class);
				return $query->order_by("$alias." . $object->nameColumn());
			case 'order':
				return $query->order_by("$alias.OrderIndex");
			case 'created':
				return $query->order_by("$alias.Created");
			default:
				return false;
		}
	}
}
