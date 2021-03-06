<?php

/**
 *
 */
namespace zesk;

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
class Selection_Type extends ORM {
	/**
	 * Modify the current selection list using the queries
	 *
	 * @param Database_Query_Select $total
	 * @param Database_Query_Select $list
	 * @param boolean $add
	 *        	True to add to the current list, false to remove
	 */
	public function items_all(Database_Query_Select $total, Database_Query_Select $list, $add = true) {
		$query = Selection_Query::instance($this, $total, $list);
		if (!$query) {
			return null;
		}
		$add = intval(to_bool($add));
		if ($query->add !== $add) {
			$query->add = $add;
		}
		$query->reorder();

		/* @var $list Database_Query_Select */
		$list = clone $list;
		$alias = $list->alias();
		$id_column = $this->application->orm_registry($list->orm_class())->id_column();
		$list->what(array(
			"id" => "$alias.$id_column",
			"*type" => $this->id(),
			"*add" => intval(to_bool($add)),
			"*query" => $query->id(),
		));
		$list->limit(0, -1);
		$this->application->orm_registry(Selection_Item::class)
			->query_insert()
			->select($list)
			->replace(true)
			->execute();
	}

	/**
	 *
	 * @return ORMIterator
	 */
	public function items_selected() {
		return $this->member_query('items')
			->where("add", true)
			->distinct(true)
			->group_by("id")
			->orm_iterator();
	}

	/**
	 * Is this selection a duplicate or not?
	 *
	 * @return boolean True if it's the user's original selection
	 */
	public function is_original() {
		return !begins($this->code, "*");
	}

	/**
	 * Delete this object and any related objects
	 *
	 * {@inheritdoc}
	 *
	 * @see ORM::delete()
	 */
	public function delete() {
		$this->application->orm_registry(Selection_Query::class)
			->query_delete()
			->where('type', $this->id)
			->execute();
		$this->application->orm_registry(Selection_Item::class)
			->query_delete()
			->where('type', $this->id)
			->execute();
		return parent::delete();
	}

	/**
	 * Copy the selection
	 *
	 * {@inheritdoc}
	 *
	 * @see ORM::duplicate($options)
	 */
	public function duplicate(Options_Duplicate &$options = null) {
		$dup = parent::duplicate($options);
		$dup->code = "*" . md5(microtime());
		$dup->store();
		$mapping = Selection_Query::copy_duplicate($this->application, $this->id, $dup->id);
		Selection_Item::copy_duplicate($this->application, $this->id, $dup->id, $mapping);
		return $dup;
	}

	/**
	 * Clear items
	 */
	public function items_clear() {
		$this->items = array();
		$this->queries = array();
		$this->store();
	}

	/**
	 * Add or remove items manually
	 *
	 * @param array $add_items
	 * @param array $remove_items
	 */
	public function items_update(array $add_items, array $remove_items) {
		$found = $this->member_query("items")->where("id", array_merge($add_items, $remove_items))->to_array("id");
		$items = ArrayTools::flip_assign($add_items, true) + ArrayTools::flip_assign($remove_items, false);
		foreach ($found as $id => $row) {
			$op = $items[$id];
			$add = to_bool($row['add']);
			$query_id = $row['query'];
			if ($add !== $op) {
				if ($op === false && !$query_id) {
					$this->application->orm_registry(Selection_Item::class)
						->query_delete()
						->where(array(
						"id" => $id,
						"type" => $this,
					))
						->execute();
				} else {
					$this->application->orm_registry(Selection_Item::class)
						->query_update()
						->value(array(
						"add" => $op,
						"query" => null,
					))
						->where(array(
						"id" => $id,
						"type" => $this,
					))
						->execute();
				}
			}
			unset($items[$id]);
		}
		foreach ($items as $id => $op) {
			$this->application->orm_registry(Selection_Item::class)
				->query_insert()
				->values(array(
				"add" => intval($op),
				"query" => null,
				"id" => $id,
				"type" => $this,
			))
				->replace(true)
				->execute();
		}
	}

	/**
	 * How many items are in my selection?
	 *
	 * @return integer
	 */
	public function count() {
		return $this->member_query("items")
			->what("*n", "COUNT(DISTINCT items.id)")
			->where("items.add", 1)
			->one_integer("n");
	}

	/**
	 * Retrive a description of the queries which make up this selection
	 */
	public function description() {
		$line = array();
		$locale = $this->application->locale;
		/* @var $query Selection_Query */
		foreach ($this->queries as $query) {
			$line[] = HTML::wrap($locale->__("[{action}] {title} ({operation}{count})", array(
				"title" => $query->title,
				"count" => $query->count(),
				"operation" => $query->add ? "+" : "-",
				"action" => $query->add ? $locale->__('Selection_Type:=Added to selection') : $locale->__('Selection_Type:=Removed from selection'),
			)), HTML::span(".query-action " . ($query->add ? ".query-action-add" : ".query-action-remove"), "[]"));
		}
		$n_added = $this->application->orm_registry("zesk\\Selection_Item")
			->query_select()
			->where(array(
			"type" => $this,
			"query" => null,
			"add" => 1,
		))
			->what("*n", "COUNT(id)")
			->one_integer("n");
		if ($n_added) {
			$line[] .= $locale->__("{n} specifically added", array(
				"n" => $n_added,
			));
		}
		$n_excluded = $this->application->orm_registry("zesk\\Selection_Item")
			->query_select()
			->where(array(
			"type" => $this,
			"query" => null,
			"add" => 0,
		))
			->what("*n", "COUNT(id)")
			->one_integer("n");
		if ($n_excluded) {
			$line[] .= $locale->__("{n} specifically removed", array(
				"n" => $n_excluded,
			));
		}
		$line[] = $locale->__("{n} total", array(
			"n" => $this->count(),
		));
		return $line;
	}
}
