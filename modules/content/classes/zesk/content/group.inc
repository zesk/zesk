<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/zesk/content/group.inc $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2008, Market Acumen, Inc.
*/

/**
 * @see Class_Zesk_Content_Group
 * @author kent
 *
 */
abstract class Zesk_Content_Group extends Object {

	function store() {
		if (empty($this->CodeName)) {
			$this->CodeName = $this->clean_code_name($this->Name);
		}
		return parent::store();
	}

	public function group_object($mixed = null, $options = null) {
		return $this->object_factory($this->group_class, $mixed, $options);
	}

	protected function order_methods() {
		return array(
			"name" => "Order by name", 
			"order" => "Order explicitly", 
			"created" => "Order by creation date"
		);
	}

	function hook_query_alter(Database_Query_Select $query) {
		$alias = $query->alias();
		switch ($this->OrderMethod) {
			case "name":
				$object = Object::cache_object($this->group_class);
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

