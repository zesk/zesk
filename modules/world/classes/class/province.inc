<?php
/**
 *
 * @author kent
 * Copyright &copy; 2013 Market Acumen, Inc.
 */
class Class_Province extends zesk\Class_Object {
	public $id_column = "id";

	public $name = "Province:=State";

	public $column_types = array(
		"id" => self::type_id,
		"country" => self::type_object,
		"code" => self::type_string,
		"name" => self::type_string
	);
	public $find_keys = array(
		"country",
		"name"
	);
	public $has_one = array(
		"country" => "Country"
	);
}
