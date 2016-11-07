<?php

/**
 * Class_Currency
 */
class Class_Currency extends zesk\Class_Object {
	public $id_column = "id";
	public $auto_column = false;

	public $find_keys = array(
		"bank_country",
		"code"
	);
	public $text_column = "name";
	public $name = "Currency";
	public $has_one = array(
		'bank_country' => 'Country'
	);
	public $column_types = array(
		"id" => self::type_id,
		"bank_country" => self::type_object,
		"name" => self::type_string,
		"code" => self::type_string,
		"symbol" => self::type_string,
		"fractional" => self::type_integer,
		"fractional_units" => self::type_string,
		"format" => self::type_string,
		"precision" => self::type_integer
	);
	public $column_defaults = array(
		'precision' => 2
	);
}
