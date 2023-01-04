<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Log_Mail
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Log_Mail
 * @author kent
 *
 */
class Class_Log_Mail extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'session' => self::TYPE_OBJECT,
		'user' => self::TYPE_OBJECT,
		'code' => self::TYPE_STRING,
		'from' => self::TYPE_STRING,
		'to' => self::TYPE_STRING,
		'subject' => self::TYPE_STRING,
		'body' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'sent' => self::TYPE_TIMESTAMP,
		'type' => self::TYPE_STRING,
		'data' => self::TYPE_SERIALIZE,
	];

	public array $has_one = [
		'session' => Session_ORM::class,
		'user' => User::class,
	];

	public array $find_keys = [
		'code',
	];
}
