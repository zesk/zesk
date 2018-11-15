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
class Schema_Contact_Email extends ORM_Schema {
    public function schema() {
        return array(
            '{table}' => array(
                'columns' => array(
                    'id' => array(
                        'type' => self::type_id,
                    ),
                    'contact' => array(
                        'type' => self::type_object,
                        'not null' => true,
                    ),
                    'label' => array(
                        'type' => self::type_object,
                        'not null' => false,
                    ),
                    'value' => array(
                        'not null' => true,
                        'size' => 128,
                        'sql_type' => 'varchar',
                        'type' => self::type_string,
                    ),
                    'created' => array(
                        'not null' => true,
                        'default' => '0',
                        'type' => self::type_created,
                    ),
                    'modified' => array(
                        'not null' => true,
                        'default' => '0',
                        'type' => self::type_modified,
                    ),
                    'verified' => array(
                        'not null' => false,
                        'default' => 0,
                        'type' => self::type_timestamp,
                    ),
                    'opt_out' => array(
                        'default' => false,
                        'type' => self::type_boolean,
                    ),
                ),
                'primary keys' => array(
                    'id' => true,
                ),
                'indexes' => array(
                    'contact' => array(
                        'contact',
                    ),
                ),
            ),
        );
    }
}
