<?php declare(strict_types=1);
/**
 *
 */
use zesk\ORM;
use zesk\Database_Query_Select;

/**
 *
 * @author kent
 *
 */
class Module_Ordering extends zesk\Module {
	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		$this->application->hooks->add(ORM::class . '::pre_insert', [
			$this,
			'object_pre_insert',
		]);
	}

	/**
	 * @param ORM $object
	 * @return zesk\Database_Query_Select
	 */
	private function query_ordering(ORM $object) {
		return $object->call_hook("query_ordering", $object->query_select("X"));
	}

	/**
	 * Name of the column used for ordering (must be an integer field)
	 * @param ORM $object
	 * @return string
	 */
	private function order_column(ORM $object) {
		return $object->class_option('order_column', $this->option('default_order_column', 'order_index'));
	}

	/**
	 * Moves an object to a position in a list, relative to other objects (IDs given)
	 *
	 * It is assumed that $previous_id and $next_id are already adjacent in the list, ordering-wise.
	 *
	 * Uses an ordering/weight column in $object to reorder. Updates
	 *
	 * @param ORM $object ORM to move
	 * @param mixed $previous_id ID of object which, after updating, will be before $object in the list
	 * @param mixed $next_id ID of object which, after updating, will be after $object in the list
	 */
	public function move(ORM $object, $previous_id, $next_id) {
		$query = $this->query_ordering($object);
		$order_column = $this->order_column($object);
		$id_column = $object->id_column();

		// Minimize the number of changes to the database
		if ($previous_id) {
			$order_index = $object->query_select("X")
				->what("X", $order_column)
				->where($id_column, $previous_id)
				->one_integer("X");
			$object->query_update()
				->value("*" . $order_column, "$order_column + 2")
				->where("$order_column|>=", $order_index + 1)
				->execute();
			return $object->set($order_column, $order_index + 1)->store();
		}
		if ($next_id) {
			$order_index = $object->query_select("X")
				->what("X", $order_column)
				->where($id_column, $next_id)
				->one_integer("X");
			$object->query_update()
				->value("*" . $order_column, "$order_column - 2")
				->where("$order_column|<=", $order_index - 1)
				->execute();
			return $object->set($order_column, $order_index - 1)->store();
		}
		return $object;
	}

	/**
	 * Implements ORM::hook_pre_insert
	 *
	 * @param ORM $object
	 * @param array $members
	 * @return array
	 */
	public function object_pre_insert(ORM $object, array $members) {
		$order_column = $this->order_column($object);
		if ($object->has_member($order_column) && $object->member_is_empty($order_column)) {
			[$func, $delta] = $object->option_bool("ordering_first") ? [
				"MIN",
				-1,
			] : [
				"MAX",
				1,
			];
			$members[$order_column] = $this->query_ordering($object)->what("*X", "$func(`$order_column`)")->one_integer("X") + $delta;
			$object->store_member($order_column, true);
		}
		return $members;
	}
}
