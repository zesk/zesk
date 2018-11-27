<?php
namespace zesk;

/**
 * @see Health_Events
 * @author kent
 *
 */
class Class_Health_Events extends Class_ORM {
    /**
     * Name of the ORM which should share this database with.
     *
     * Allows objects to be grouped into a database (by module) or functionality, for example.
     *
     * @var string
     */
    protected $database_group = "Health_Event";

    public $id_column = "id";

    public $find_keys = array(
        'date',
        'hash',
    );

    public $has_one = array(
        "server" => "zesk\\Server",
    );

    public $column_types = array(
        "id" => self::type_id,

        "date" => self::type_date,
        "hash" => self::type_hex,

        "server" => self::type_object,
        "application" => self::type_string,
        "context" => self::type_string,
        "type" => self::type_string,
        "message" => self::type_string,
        "fatal" => self::type_boolean,

        "first" => self::type_timestamp,
        "first_msec" => self::type_integer,
        "recent" => self::type_timestamp,
        "recent_msec" => self::type_integer,

        "total" => self::type_integer,
    );

    public $column_defaults = array(
        'first_msec' => 0,
        'recent_msec' => 0,
    );
}
