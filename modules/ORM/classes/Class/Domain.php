<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see Domain
 * @author kent
 *
 */
class Class_Domain extends Class_Base {
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
		'id' => self::type_id,
		'name' => self::type_string,
		'tld' => self::type_string,
	];
}
