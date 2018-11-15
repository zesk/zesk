<?php
namespace zesk;

class Schema_Contact extends ORM_Schema {
    public function schema() {
        return array(
            '{table}' => array(
                'columns' => array(
                    'id' => array(
                        'not null' => true,
                        'increment' => true,
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'hash' => array(
                        'not null' => true,
                        'size' => 16,
                        'type' => 'binary(16)',
                        'sql_type' => 'binary(16)',
                    ),
                    'account' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'user' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'keywords' => array(
                        'type' => 'text',
                        'sql_type' => 'text',
                    ),
                    'person' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'email' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'address' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'phone' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'url' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'notes' => array(
                        'type' => 'text',
                        'sql_type' => 'text',
                    ),
                    'created' => array(
                        'not null' => true,
                        'default' => '0',
                        'type' => 'timestamp',
                        'sql_type' => 'timestamp',
                    ),
                    'modified' => array(
                        'not null' => true,
                        'default' => '0',
                        'type' => 'timestamp',
                        'sql_type' => 'timestamp',
                    ),
                    'connector' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'verified' => array(
                        'type' => 'timestamp',
                        'sql_type' => 'timestamp',
                    ),
                    'verifier' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                    'duplicate' => array(
                        'unsigned' => true,
                        'type' => 'integer',
                        'sql_type' => 'integer',
                    ),
                ),
                'primary keys' => array(
                    'id',
                ),
                'indexes' => array(
                    'e' => array(
                        'email',
                    ),
                    'a' => array(
                        'address',
                    ),
                    'p' => array(
                        'phone',
                    ),
                    'v' => array(
                        'verifier',
                    ),
                ),
            ),
        );
    }
}
