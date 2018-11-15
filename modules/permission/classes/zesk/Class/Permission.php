<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_Permission extends Class_ORM {
    /**
     * ORM id column
     *
     * @var string
     */
    public $id_column = "id";
    
    /**
     * How to find one of these without an id?
     *
     * @var array
     */
    public $find_keys = array(
        'name',
    );
    
    /**
     * Special handling of columns
     *
     * @var array
     */
    public $column_types = array(
        "id" => self::type_id,
        "name" => self::type_string,
        "title" => self::type_string,
        "class" => self::type_string,
        "hook" => self::type_string,
        "options" => self::type_serialize,
    );

    public $database_group = "User";
}
