<?php declare(strict_types=1);
/**
 * @version $Id: Preference.php 4412 2017-03-08 05:16:44Z kent $
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Class_Preference extends Class_Base {
	public string $id_column = 'id';

	/**
	 * Column type definition for Object
	 *
	 * @var unknown
	 */
	public array $column_types = [
		'id' => self::type_id,
		'user' => self::type_object,
		'type' => self::type_object,
		'value' => self::type_serialize,
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
		'user' => 'zesk\\User',
		'type' => 'zesk\\Preference_Type',
	];
}
