<?php
namespace zesk;

class Class_Meta extends Class_ORM {
    public $primary_keys = array(
        "parent",
        "name",
    );

    public $column_types = array(
        "parent" => self::type_object,
        "name" => self::type_string,
        "value" => self::type_serialize,
    );
    
    /**
     * Overwrite this in subclasses to change stuff upon instantiation
     */

    /**
     * Configure a class prior to instantiation
     *
     * Only thing set is "$this->class"
     */
    protected function configure(ORM $object) {
        if (!$this->table) {
            $this->initialize_database($object);
            $this->table = $this->database()->table_prefix() . PHP::parse_class(get_class($object));
        }
    }
}
