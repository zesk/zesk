<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Log_Mail
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Log_Mail
 * @author kent
 *
 */
class Class_Log_Mail extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public string $id_column = "id";

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'session' => self::type_object,
		'user' => self::type_object,
		'code' => self::type_string,
		'from' => self::type_string,
		'to' => self::type_string,
		'subject' => self::type_string,
		'body' => self::type_string,
		'created' => self::type_created,
		'sent' => self::type_timestamp,
		'type' => self::type_string,
		'data' => self::type_serialize,
	];

	public array $has_one = [
		'session' => Session_ORM::class,
		'user' => User::class,
	];

	public array $find_keys = [
		'code',
	];
}
