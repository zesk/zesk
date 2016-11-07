<?php
/**
 * 
 */
use zesk\Object;
use zesk\Database_Query_Select;

/**
 * @see Class_Selection_Query
 * @author kent
 *
 */
class Selection_Query extends Object {
	
	/**
	 * Fetch or create a query
	 * 
	 * @param Selection_Type $type
	 * @param zesk\Database_Query_Select $total
	 * @param zesk\Database_Query_Select $list
	 * @return self
	 */
	public static function instance(Selection_Type $type, zesk\Database_Query_Select $total, zesk\Database_Query_Select $list) {
		$code = md5(serialize(strval($list)));
		$object = $type->application->object_factory(__CLASS__, array(
			"type" => $type,
			"code" => $code
		));
		if (($found = $object->find()) !== null) {
			return $found;
		}
		$object->title = $list->title();
		// Remove limit
		$list->limit(0, -1);
		$object->query_total = $total;
		$object->query_list = $list;
		return $object->store();
	}
	public function reorder() {
		$this->order_index = $this->query_select("X")
			->where("X.type", $this->type)
			->what("*N", "MAX(X.order_index)")
			->one_integer("N", 0) + 1;
		return $this->store();
	}
	public function count() {
		$query = $this->query_total;
		if ($query instanceof zesk\Database_Query_Select) {
			return $query->one_integer("total", 0);
		}
		return "unknown";
	}
	public static function copy_duplicate($old_type, $new_type) {
		$map = array();
		/* @var $query Selection_Query */
		foreach (app()->query_select(__CLASS__)->where('type', $old_type)->object_iterator() as $query) {
			$old_id = $query->id;
			$query->id = null;
			$query->type = $new_type;
			$query->store();
			$map[$old_id] = $query->id;
		}
		return $map;
	}
}
