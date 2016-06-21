<?php

/**
 * @see Selection_Query
 * @author kent
 */
class Class_Selection_Query extends Class_Object {
	public $id_column = "id";
	public $has_one = array(
		'type' => 'Selection_Type'
	);
	public $find_keys = array(
		'type',
		'code'
	);
	public $column_types = array(
		'id' => self::type_id,
		'type' => self::type_object,
		'code' => self::type_hex,
		'order_index' => self::type_integer,
		'title' => self::type_text,
		'query_total' => self::type_serialize,
		'query_list' => self::type_serialize,
		'created' => self::type_created,
		'add' => self::type_boolean
	);
}