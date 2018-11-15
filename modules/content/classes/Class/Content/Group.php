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
class Class_Content_Group extends Class_ORM {
    /**
     *
     * @var array
     */
    public $column_types = array(
        "id" => self::type_id,
        "code" => self::type_string,
        "name" => self::type_string,
        "body" => self::type_string,
        "created" => self::type_created,
        "modified" => self::type_modified,
        "order_by" => self::type_string,
    );
    
    /**
     *
     * @var string
     */
    public $group_class = null;
}
