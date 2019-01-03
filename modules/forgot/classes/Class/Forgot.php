<?php
namespace zesk;

/**
 * @see Forgot
 */
class Class_Forgot extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public $id_column = "id";

	/**
	 *
	 * @var array
	 */
	public $find_keys = array(
		'code',
	);

	/**
	 *
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"login" => self::type_string,
		"user" => self::type_object,
		"session" => self::type_object,
		"code" => self::type_hex,
		"created" => self::type_created,
		'updated' => self::type_timestamp,
	);

	/**
	 *
	 * @var array
	 */
	public $has_one = array(
		"user" => User::class,
		"session" => Session_ORM::class,
	);

	/**
	 *
	 * @var string
	 */
	public $database_group = User::class;
}
