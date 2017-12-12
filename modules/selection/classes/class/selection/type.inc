<?php
/**
 * 
 */
namespace zesk;

/**
 * @see Selection_Type
 * @author kent
 *
 */
class Class_Selection_Type extends Class_ORM {
	public $id_column = "id";
	public $find_keys = array(
		"code",
		"user"
	);
	public $has_one = array(
		"user" => "zesk\\User"
	);
	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_text,
		"class" => self::type_text,
		"user" => self::type_object,
		"created" => self::type_created
	);
	public $has_many = array(
		'items' => array(
			'class' => "zesk\\Selection_Item",
			"foreign_key" => "type"
		),
		'queries' => array(
			"class" => "zesk\\Selection_Query",
			"foreign_key" => "type",
			"order_by" => "order_index"
		)
	);
}
