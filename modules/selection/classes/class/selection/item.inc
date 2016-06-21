<?php

/**
 * @see Selection_Item
 * @author kent
 *
 */
class Class_Selection_Item extends Class_Object {

	public $id_column = false;

	public $primary_keys = array(
		'type',
		'id'
	);

	public $has_one = array(
		'type' => 'Selection_Type',
		'query' => 'Selection_Query',
	);

	public $column_types = array(
		'type' => self::type_object,
		'id' => self::type_object,
		'add' => self::type_boolean,
		'query' => self::type_object
	);
}