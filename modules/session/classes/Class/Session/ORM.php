<?php
namespace zesk;

/**
 * @see Session_ORM
 * @author kent
 *
 */
class Class_Session_ORM extends Class_ORM {
    /**
     * ID Column
     *
     * @var string
     */
    public $id_column = "id";

    public $find_keys = array(
        "cookie",
    );

    public $has_one = array(
        "user" => "zesk\User",
    );

    public $column_types = array(
        "id" => self::type_id,
        "cookie" => self::type_string,
        "is_one_time" => self::type_boolean,
        "user" => self::type_object,
        "ip" => self::type_ip4,
        "created" => self::type_created,
        "modified" => self::type_modified,
        "expires" => self::type_datetime,
        "seen" => self::type_datetime,
        "sequence_index" => self::type_integer,
        "data" => self::type_serialize,
    );

    public $code_name = "Session";

    public $column_defaults = array(
        'data' => array(),
        'sequence_index' => 0,
        'ip' => '127.0.0.1',
    );
}
