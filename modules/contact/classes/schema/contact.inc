<?php

class Schema_Contact extends Database_Schema {
	function schema() {
		return array(
			'{table}' => array(
				'columns' => array(
					'ID' => array(
						'not null' => true,
						'increment' => true,
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'ID'
					),
					'Hash' => array(
						'not null' => true,
						'size' => 16,
						'type' => 'binary(16)',
						'sql_type' => 'binary(16)',
						'name' => 'Hash'
					),
					'Account' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Account'
					),
					'User' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'User'
					),
					'Keywords' => array(
						'type' => 'text',
						'sql_type' => 'text',
						'name' => 'Keywords'
					),
					'Person' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Person'
					),
					'Email' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Email'
					),
					'Address' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Address'
					),
					'Phone' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Phone'
					),
					'URL' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'URL'
					),
					'Notes' => array(
						'type' => 'text',
						'sql_type' => 'text',
						'name' => 'Notes'
					),
					'Created' => array(
						'not null' => true,
						'default' => '0',
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
						'name' => 'Created'
					),
					'Modified' => array(
						'not null' => true,
						'default' => '0',
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
						'name' => 'Modified'
					),
					'Connector' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Connector'
					),
					'Verified' => array(
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
						'name' => 'Verified'
					),
					'Verifier' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Verifier'
					),
					'Duplicate' => array(
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
						'name' => 'Duplicate'
					)
				),
				'primary keys' => array(
					'ID'
				),
				'indexes' => array(
					'e' => array(
						'Email'
					),
					'a' => array(
						'Address'
					),
					'p' => array(
						'Phone'
					),
					'v' => array(
						'Verifier'
					)
				)
			)
		);
	}
}

