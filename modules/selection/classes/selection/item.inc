<?php

/**
 * @see Class_Selection_Item
 * @author kent
 *
 */
class Selection_Item extends Object {
	/**
	 * Value in "add" for items which should be removed from the query
	 * @var integer
	 */
	const type_remove = 0;
	/**
	 * Value in "add" for items which should be added to the query
	 * @var integer
	 */
	const type_add = 1;

	public function item($class) {
		try {
			return $this->object_factory($class, $this->id)->fetch();
		} catch (Exception_Object_NotFound $e) {
			return null;
		}
	}
	public static function copy_duplicate($old_type, $new_type, array $mapping) {
		/* @var $class Class_Object */
		$class = Object::cache_class(__CLASS__, 'class');
		$members = $class->column_types;
		unset($members['type']);
		$what = arr::flip_copy(array_keys($members));
		$what['*type'] = $new_type;
		Object::class_query_insert_select(__CLASS__)->what($what)->where('type', $old_type)->execute();
		foreach ($mapping as $old_query_id => $new_query_id) {
			Object::class_query_update(__CLASS__)->value('query', $new_query_id)->where(array(
				'query' => $old_query_id,
				'type' => $new_type
			))->execute();
		}
	}
}