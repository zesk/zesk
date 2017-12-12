<?php
namespace zesk;

/**
 * @see zesk\Tag
 * @see Tag_Label
 * @author kent
 *
 */
class Class_Tag extends Class_ORM {
	/**
	 * 
	 * @var string
	 */
	public $id_column = 'id';
	public $find_keys = array(
		'tag_label',
		'object_class',
		'object_id'
	);
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		'id' => self::type_id,
		'tag_label' => self::type_object,
		'object_class' => self::type_string,
		'object_id' => self::type_object,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'value' => self::type_serialize
	);
	
	/**
	 * 
	 * @var array
	 */
	public $has_one = array(
		'tag_label' => 'zesk\\Tag_Label',
		'object_id' => '*object_class'
	);
}
