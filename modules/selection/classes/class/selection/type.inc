<?php

/**
 * @see Selection_Type
 * @author kent
 *
 */
class Class_Selection_Type extends Class_Object {
	public $id_column = "id";

	public $find_keys = array(
		"code",
		"user"
	);

	public $has_one = array(
		"user" => "User"
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
			'class' => "Selection_Item",
			"foreign_key" => "type"
		),
		'queries' => array(
			"class" => "Selection_Query",
			"foreign_key" => "type",
			"order_by" => "order_index",
		)
	);
}