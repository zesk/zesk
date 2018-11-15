<?php
/**
 *
 */
namespace zesk;

/**
 * @see Content_Image
 * @author kent
 */
class Class_Content_Image extends Class_ORM {
    /**
     *
     * @var string
     */
    public $id_column = "id";
    
    /**
     *
     * @var string
     */
    public $name = "Image";
    
    /**
     *
     * @var string
     */
    public $name_column = "title";
    
    /**
     *
     * @var array
     */
    public $find_keys = array(
        "data",
        "path",
    );
    
    /**
     *
     * @var array
     */
    public $has_one = array(
        "data" => "zesk\Content_Data",
    );
    
    /**
     *
     * @var array
     */
    public $has_many = array(
        "users" => array(
            "class" => "zesk\User",
            "link_class" => "zesk\User_Content_Image",
            "foreign_key" => "image",
            "far_key" => "user",
        ),
    );
    
    /**
     *
     * @var array
     */
    public $column_types = array(
        "id" => self::type_id,
        "data" => self::type_object,
        "width" => self::type_integer,
        "height" => self::type_integer,
        "mime_type" => self::type_string,
        "path" => self::type_string,
        "title" => self::type_string,
        "description" => self::type_string,
        "created" => self::type_created,
        "modified" => self::type_modified,
    );
    
    /**
     *
     * @var array
     */
    public $column_defaults = array(
        'title' => '',
        "description" => "",
    );
}
