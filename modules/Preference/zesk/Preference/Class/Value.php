<?php
declare(strict_types=1);
/**
 * @version $Id: Preference.php 4412 2017-03-08 05:16:44Z kent $
 * @package zesk
 * @subpackage user
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Preference;

use zesk\ORM\Class_Base;
use zesk\ORM\User;

class Class_Value extends Class_Base {
	public string $code_name = 'Preference';

	public string $id_column = 'id';

	/**
	 * Column type definition for Object
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'user' => self::TYPE_OBJECT,
		'type' => self::TYPE_OBJECT,
		'value' => self::TYPE_SERIALIZE,
	];

	/**
	 * Which keys are used to find this in the database uniquely
	 *
	 * @var array
	 */
	public array $find_keys = [
		'user',
		'type',
	];

	/**
	 * Links to other objects
	 *
	 * @var array
	 */
	public array $has_one = [
		'user' => User::class,
		'type' => Type::class,
	];
}
