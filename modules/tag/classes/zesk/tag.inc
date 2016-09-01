<?php

namespace zesk;

use \Object_Iterator as Object_Iterator;

/**
 * @see Class_Tag
 * @see Module_Tag
 * @property id $id
 * @property Tag_Label $tag_label
 * @property string $object_class
 * @property Object $object_id
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property mixed $value
 */
class Tag extends Object {
	
	/**
	 * Delete items matching the class and IDs passed
	 * 
	 * @param unknown $class
	 * @param array $ids
	 * @return integer
	 */
	private static function delete_class_ids($class, array $ids) {
		if (count($ids) === 0) {
			return 0;
		}
		return Object::class_query_delete(__CLASS__)->where(array(
			"object_class" => $class,
			"object_id" => $ids
		))->execute();
	}
	
	/**
	 * Delete tags linked to deleted rows
	 * 
	 * @param \Application $application
	 */
	public static function cull(\Application $application) {
		$classes = Object::class_query(__CLASS__)->what("object_class", "object_class")->distinct(true)->to_array(null, "object_class");
		foreach ($classes as $class) {
			/* @var $class_object Class_Object */
			$class_object = Object::cache_class($class, "class");
			$id_column = $class_object->id_column;
			$id_iterator = Object::class_query(__CLASS__, "X")->what("X.object_id")
				->distinct(true)
				->join("LEFT OUTER JOIN {table} linked ON X.object_id=linked.$id_column")
				->where("X.object_class", $class)
				->where("linked.$id_column", NULL)
				->iterator(null, "object_id");
			$id_batch = array();
			foreach ($id_iterator as $id) {
				$id_batch[] = $id;
				if (count($id_batch) >= 100) {
					self::delete_class_ids($class, $id_batch);
					$id_batch = array();
				}
				self::delete_class_ids($class, $id_batch);
			}
		}
	}
	
	/**
	 * Can I apply tags to this object?
	 * 
	 * @param Object $object
	 * @param boolean $throw Throw exception in case of error
	 * @throws \Exception_Semantics
	 * @return boolean
	 */
	public static function can_tag_object(Object $object, $throw = false) {
		$class = $object->class;
		$id_column = $class->id_column;
		if (!$id_column) {
			if ($throw) {
				throw new \Exception_Semantics("Unable to tag class {class} - no id column", array(
					"class" => get_class($object)
				));
			}
			return false;
		}
		if ($class->column_types[$id_column] !== Class_Object::type_id) {
			if ($throw) {
				throw new \Exception_Semantics("Unable to tag class {class} - id column is not an ID (set to {type}", array(
					"class" => get_class($object),
					"type" => $class->column_types[$id_column]
				));
			}
			return false;
		}
		return true;
	}
	
	/**
	 * Set a tag to an object
	 * 
	 * @param \Object $object
	 * @param string $name
	 * @param mixed $value
	 * @param array $label_attributes
	 * @return zesk\Tag
	 */
	public static function tag_set(\Object $object, Tag_Label $label, $value) {
		self::can_tag_object($object, true);
		$members = array(
			'tag_label' => $label,
			'object_class' => get_class($object),
			'object_id' => $object->id(),
			'data' => $value
		);
		$object = self::factory(__CLASS__, $members);
		if ($object->find()) {
			return $object->set_member($members)->store();
		}
		return $object->store();
	}
	
	/**
	 * Retrieve a value 
	 * @param \Object $object
	 * @param string|Tag_Label $name
	 * @param mixed $default
	 * @return mixed 
	 */
	public static function tag_get_value(Object $object, $code, $default = null) {
		$tag = self::tag_get($object, $code);
		return $tag ? $tag->value : $default;
	}
	
	/**
	 * Retrieve all tags for an object
	 * 
	 * @param \Object $object
	 * @param string|Tag_Label $name
	 * @return zesk\Tag
	 */
	public static function tag_get(\Object $object, $code) {
		self::can_tag_object($object, true);
		if ($code instanceof Tag_Label) {
			$label = $code;
		} else {
			$label = Tag_Label::tag_find($code);
		}
		if (!$label) {
			return null;
		}
		$members = array(
			'tag_label' => $label,
			'object_class' => get_class($object),
			'object_id' => $object->id()
		);
		$object = self::factory(__CLASS__, $members);
		if ($object->find()) {
			return $object;
		}
		return null;
	}
	
	/**
	 * 
	 * @param \Object $object
	 * @param array $where
	 * @return \Object_Iterator
	 */
	public static function tags(Object $object, array $where = array()) {
		self::can_tag_object($object, true);
		$query = self::class_query(__CLASS__, "X")->where(array(
			"object_class" => get_class($object),
			"object_id" => $object->id()
		));
		$query->link("zesk\\Tag_Label", array(
			"alias" => "label"
		));
		if (count($where) > 0) {
			$query->where($where);
		}
		return $query->object_iterator("link.code");
	}
}