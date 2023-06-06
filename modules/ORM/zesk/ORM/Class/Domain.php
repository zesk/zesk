<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see Host
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
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'tld' => self::TYPE_STRING,
	];
}
