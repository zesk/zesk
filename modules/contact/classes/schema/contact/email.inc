<?php

class Schema_Contact_Email extends Database_Schema {
	function schema() {
		return array(
			'{table}' => array(
				'columns' => array(
					'ID' => array(
						'type' => 'id'
					), 
					'Contact' => array(
						'type' => 'object', 
						'not null' => true
					), 
					'Label' => array(
						'type' => 'object', 
						'not null' => false
					), 
					'Value' => array(
						'not null' => true, 
						'size' => 128, 
						'type' => 'varchar'
					), 
					'Created' => array(
						'not null' => true, 
						'default' => '0', 
						'type' => 'timestamp'
					), 
					'Modified' => array(
						'not null' => true, 
						'default' => '0', 
						'type' => 'timestamp'
					), 
					'Verified' => array(
						'not null' => false, 
						'default' => 0, 
						'type' => 'timestamp'
					), 
					'OptOut' => array(
						'default' => 'false', 
						'type' => 'boolean'
					)
				), 
				'primary keys' => array(
					'ID' => true
				), 
				'indexes' => array(
					'Contact' => array(
						'Contact' => true
					)
				)
			)
		);
	}
}

