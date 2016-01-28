<?php

/**
 *
 * @author kent
 * @see Class_Selection_Type
 * @property id $id
 * @property string $code
 * @property string $class
 * @property User $user
 * @property Timestamp $created
 */
class Selection_Type extends Object {

	public function items_all(Database_Query_Select $total, Database_Query_Select $list) {
		$query = Selection_Query::instance($this, $total, $list);
		if (!$query) {
			return null;
		}
		/* @var $list Database_Query_Select */
		$list = clone $list;
		$alias = $list->alias();
		$id_column = Object::class_id_column($list->object_class());
		$list->what(array(
			"id" => "$alias.$id_column",
			"*type" => $this->id(),
			"*add" => true,
			"*query" => $query->id()
		));
		Object::class_query_insert("Selection_Item")->select($list)->replace(true)->execute();

	}

	/**
	 * @return Object_Iterator
	 */
	public function items_selected() {
		return $this->member_query('items')
			->where("add", true)
			->distinct(true)
			->group_by("id")
			->object_iterator();
	}

	/**
	 * Is this selection a duplicate or not?
	 *
	 * @return boolean True if it's the user's original selection
	 */
	public function is_original() {
		return !begins($this->code, "*");
	}

	public function delete() {
		Object::class_query_delete('Selection_Query')->where('type', $this->id)->execute();
		Object::class_query_delete('Selection_Item')->where('type', $this->id)->execute();
		return parent::delete();
	}

	public function duplicate(Options_Duplicate &$options = null) {
		$dup = parent::duplicate($options);
		$dup->code = "*" . md5(microtime());
		$dup->store();
		$mapping = Selection_Query::copy_duplicate($this->id, $dup->id);
		Selection_Item::copy_duplicate($this->id, $dup->id, $mapping);
		return $dup;
	}

	public function items_clear() {
		$this->items = array();
		$this->queries = array();
		$this->store();
	}
	public function items_update(array $add_items, array $remove_items) {
		$found = $this->member_query("items")->where("id", array_merge($add_items, $remove_items))->to_array("id");
		$items = arr::flip_assign($add_items, true) + arr::flip_assign($remove_items, false);
		foreach ($found as $id => $row) {
			$op = $items[$id];
			$add = to_bool($row['add']);
			$query_id = $row['query'];
			if ($add !== $op) {
				if ($op === false && !$query_id) {
					Object::class_query_delete("Selection_Item")->where(array(
						"id" => $id,
						"type" => $this
					))->execute();
				} else {
					Object::class_query_update("Selection_Item")->value("add", $op)->where(array(
						"id" => $id,
						"type" => $this
					))->execute();
				}
			}
			unset($items[$id]);
		}
		foreach ($items as $id => $op) {
			Object::class_query_insert("Selection_Item")->values(array(
				"add" => intval($op),
				"id" => $id,
				"type" => $this
			))->replace(true)->execute();
		}
	}

	public function count() {
		return $this->member_query("items")->what("*n", "COUNT(DISTINCT items.id)")->where("items.add", 1)->one_integer("n");
	}

	public function description() {
		$line = array();
		/* @var $query Selection_Query */
		foreach ($this->queries as $query) {
			$line[] = __("{title} ({count})", array(
				"title" => $query->title,
				"count" => $query->count()
			));
		}
		$n_added = Object::class_query("Selection_Item")->where(array(
			"type" => $this,
			"query" => null,
			"add" => 1
		))->what("*n", "COUNT(id)")->one_integer("n");
		if ($n_added) {
			$line[] .= __("{n} specifically added", array(
				"n" => $n_added
			));
		}
		$n_excluded = Object::class_query("Selection_Item")->where(array(
			"type" => $this,
			"add" => 0
		))->what("*n", "COUNT(id)")->one_integer("n");
		if ($n_excluded) {
			$line[] .= __("{n} specifically excluded", array(
				"n" => $n_excluded
			));
		}
		$line[] = __("{n} total", array(
			"n" => $this->count()
		));
		return $line;
	}
}
