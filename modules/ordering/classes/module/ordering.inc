<?php
class Module_Ordering extends Module {
	/**
	 * @param Object $object
	 * @return Database_Query_Select
	 */
	private static function query_ordering(Object $object) {
		return $object->hook("query_ordering", $object->query("X"));
	}
	/**
	 * Name of the column used for ordering (must be an integer field)
	 * @param Object $object
	 * @return string
	 */
	private static function order_column(Object $object) {
		return $object->class_option('order_column', zesk::get('Module_Ordering::default_order_column', 'order_index'));
	}
	/**
	 * Moves an object to a position in a list, relative to other objects (IDs given)
	 *
	 * It is assumed that $previous_id and $next_id are already adjacent in the list, ordering-wise.
	 *
	 * Uses an ordering/weight column in $object to reorder. Updates
	 *
	 * @param Object $object Object to move
	 * @param mixed $previous_id ID of object which, after updating, will be before $object in the list
	 * @param mixed $next_id ID of object which, after updating, will be after $object in the list
	 */
	public static function move(Object $object, $previous_id, $next_id) {
		$query = self::query_ordering($object);
		$order_column = self::order_column($object);
		$id_column = $object->id_column();

		// Minimize the number of changes to the database
		if ($previous_id) {
			$order_index = $object->query("X")->what("X", $order_column)->where($id_column, $previous_id)->one_integer("X");
			$object->query_update()->value("*" . $order_column, "$order_column + 2")->where("$order_column|>=", $order_index + 1)->execute();
			return $object->set($order_column, $order_index + 1)->store();
		}
		if ($next_id) {
			$order_index = $object->query("X")->what("X", $order_column)->where($id_column, $next_id)->one_integer("X");
			$object->query_update()->value("*" . $order_column, "$order_column - 2")->where("$order_column|<=", $order_index - 1)->execute();
			return $object->set($order_column, $order_index - 1)->store();
		}
		return $object;
	}

	/**
	 * Implements Object::hook_pre_insert
	 *
	 * @param Object $object
	 * @param array $members
	 * @return array
	 */
	public static function object_pre_insert(Object $object, array $members) {
		$order_column = self::order_column($object);
		if ($object->has_member($order_column) && $object->member_is_empty($order_column)) {
			$members[$order_column] = self::query_ordering($object)->what("*X", "MAX(`$order_column`)")->one_integer("X") + 1;
			$object->store_member($order_column, true);
		}
		return $members;
	}
}
