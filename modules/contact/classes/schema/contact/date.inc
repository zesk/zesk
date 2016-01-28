<?php

class Schema_Contact_Date extends Database_Schema {
	function schema() {
		return $this->map(array(
			'{table}' => array(
				'columns' => array(
					'id' => array(
						'type' => 'id'
					),
					'contact' => array(
						'type' => 'object'
					),
					'label' => array(
						'type' => 'object'
					),
					'value' => array(
						'not null' => true,
						'default' => '0000-00-00',
						'type' => 'date'
					),
					'created' => array(
						'not null' => true,
						'type' => 'timestamp',
						'default' => '0'
					),
					'modified' => array(
						'type' => 'timestamp',
						'not null' => true,
						'default' => '0'
					)
				),
				'primary keys' => array(
					'id' => true
				),
				'indexes' => array(
					'ContactDates' => array(
						'contact',
						'value'
					)
				)
			)
		));
	}
}

