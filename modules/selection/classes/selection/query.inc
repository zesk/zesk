<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @see Class_Selection_Query
 * @author kent
 *
 */
class Selection_Query extends Object {

	/**
	 * Fetch or create a query
	 *
	 * @param Selection_Type $type
	 * @param Database_Query_Select $total
	 * @param Database_Query_Select $list
	 * @return self
	 */
	public static function instance(Selection_Type $type, Database_Query_Select $total, Database_Query_Select $list) {
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
		$this->order_index = $this->query_select("X")->where("X.type", $this->type)->what("*N", "MAX(X.order_index)")->one_integer("N", 0) + 1;
		return $this->store();
	}
	public function count() {
		$query = $this->query_total;
		if ($query instanceof Database_Query_Select) {
			return $query->one_integer("total", 0);
		}
		return "unknown";
	}

	/**
	 *
	 * @param Application $application
	 * @param string $old_type
	 * @param string $new_type
	 * @return integer[integer] Old IDs as keys, new IDs as values
	 */
	public static function copy_duplicate(Application $application, $old_type, $new_type) {
		$map = array();
		/* @var $query Selection_Query */
		foreach ($application->query_select(__CLASS__)->where('type', $old_type)->object_iterator() as $query) {
			$old_id = $query->id;
			$query->id = null;
			$query->type = $new_type;
			$query->store();
			$map[$old_id] = $query->id;
		}
		return $map;
	}
}
