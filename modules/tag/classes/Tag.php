<?php
/**
 *
 */
namespace zesk;

/**
 * @see Class_Tag
 * @see Module_Tag
 * @property id $id
 * @property Tag_Label $tag_label
 * @property string $object_class
 * @property ORM $object_id
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property mixed $value
 */
class Tag extends ORM {
	
	/**
	 * Delete items matching the class and IDs passed
	 *
	 * @param unknown $class
	 * @param array $ids
	 * @return integer
	 */
	private static function delete_class_ids(Application $application, $class, array $ids) {
		if (count($ids) === 0) {
			return 0;
		}
		return $application->orm_registry(__CLASS__)
			->query_delete()
			->where(array(
			"object_class" => $class,
			"object_id" => $ids
		))
			->execute()
			->affected_rows();
	}
	
	/**
	 * Delete tags linked to deleted rows
	 *
	 * @param Application $application
	 */
	public static function cull(Application $application) {
		$classes = $application->orm_registry(__CLASS__)
			->query_select()
			->what("object_class", "object_class")
			->distinct(true)
			->to_array(null, "object_class");
		foreach ($classes as $class) {
			/* @var $class_object Class_ORM */
			$class_object = $application->class_orm_registry($class);
			$id_column = $class_object->id_column;
			$id_iterator = $application->orm_registry(__CLASS__)
				->query_select("X")
				->what("X.object_id")
				->distinct(true)
				->join("LEFT OUTER JOIN {table} linked ON X.object_id=linked.$id_column")
				->where("X.object_class", $class)
				->where("linked.$id_column", NULL)
				->iterator(null, "object_id");
			$id_batch = array();
			foreach ($id_iterator as $id) {
				$id_batch[] = $id;
				if (count($id_batch) >= 100) {
					self::delete_class_ids($application, $class, $id_batch);
					$id_batch = array();
				}
				self::delete_class_ids($application, $class, $id_batch);
			}
		}
	}
	
	/**
	 * Can I apply tags to this object?
	 *
	 * @param ORM $object
	 * @param boolean $throw Throw exception in case of error
	 * @throws \Exception_Semantics
	 * @return boolean
	 */
	public static function can_tag_object(ORM $object, $throw = false) {
		$class = $object->class;
		$id_column = $class->id_column;
		if (!$id_column) {
			if ($throw) {
				throw new Exception_Semantics("Unable to tag class {class} - no id column", array(
					"class" => get_class($object)
				));
			}
			return false;
		}
		if ($class->column_types[$id_column] !== Class_ORM::type_id) {
			if ($throw) {
				throw new Exception_Semantics("Unable to tag class {class} - id column is not an ID (set to {type}", array(
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
	 * @param ORM $object
	 * @param string $name
	 * @param mixed $value
	 * @param array $label_attributes
	 * @return zesk\Tag
	 */
	public static function tag_set(ORM $object, Tag_Label $label, $value) {
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
	 * @param ORM $object
	 * @param string|Tag_Label $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function tag_get_value(ORM $object, $code, $default = null) {
		$tag = self::tag_get($object, $code);
		return $tag ? $tag->value : $default;
	}
	
	/**
	 * Retrieve all tags for an object
	 *
	 * @param ORM $object
	 * @param string|Tag_Label $name
	 * @return zesk\Tag
	 */
	public static function tag_get(ORM $object, $code) {
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
	 * @param ORM $object
	 * @param array $where
	 * @return ORMIterator
	 */
	public static function tags(ORM $object, array $where = array()) {
		self::can_tag_object($object, true);
		$query = $object->application->orm_registry(__CLASS__)->query_select("X")->where(array(
			"object_class" => get_class($object),
			"object_id" => $object->id()
		));
		$query->link(Tag_Label::class, array(
			"alias" => "label"
		));
		if (count($where) > 0) {
			$query->where($where);
		}
		return $query->orm_iterator(); // TODO used to take parameter "link.code" - where is this used and are results OK as is?
	}
}
