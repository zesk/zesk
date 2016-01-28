<?php

/**
 * @see Class_Selection_Query
 * @author kent
 *
 */
class Selection_Query extends Object {
	public static function instance(Selection_Type $type, Database_Query_Select $total, Database_Query_Select $list) {
		$code = md5(serialize($list));
		$object = Object::factory(__CLASS__, array(
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

	public function count() {
		$query = $this->query_total;
		if ($query instanceof Database_Query_Select) {
			return $query->one_integer("total", 0);
		}
		return "unknown";
	}

	public static function copy_duplicate($old_type, $new_type) {
		$map = array();
		/* @var $query Selection_Query */
		foreach (Object::class_query(__CLASS__)->where('type', $old_type)->object_iterator() as $query) {
			$old_id = $query->id;
			$query->id = null;
			$query->type = $new_type;
			$query->store();
			$map[$old_id] = $query->id;
		}
		return $map;
	}
}