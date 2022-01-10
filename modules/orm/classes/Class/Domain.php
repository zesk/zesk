<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Domain
 * @author kent
 *
 */
class Class_Domain extends Class_ORM {
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
		"id" => self::type_id,
		"name" => self::type_string,
		"tld" => self::type_string,
	];
}
