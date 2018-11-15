<?php
/**
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_Preference_Type extends Class_ORM {
    public $id_column = "id";

    public $column_types = array(
        "id" => self::type_id,
        "code" => self::type_string,
        "name" => self::type_string,
        "description" => self::type_string,
    );

    public $has_many = array(
        'preferences' => array(
            'class' => "zesk\\Preference",
            'foreign_key' => 'type',
        ),
    );

    public $find_keys = array(
        "code",
    );

    public $database_group = "zesk\\Preference";
}
