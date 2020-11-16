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
class Schema_Contact_Date extends ORM_Schema {
	public function schema() {
		return $this->map(array(
			'{table}' => array(
				'columns' => array(
					'id' => array(
						'type' => self::type_id,
					),
					'contact' => array(
						'type' => self::type_object,
					),
					'label' => array(
						'type' => self::type_object,
					),
					'value' => array(
						'not null' => true,
						'type' => self::type_date,
					),
					'created' => array(
						'not null' => true,
						'type' => self::type_timestamp,
						'default' => '0000-00-00 00:00:00',
					),
					'modified' => array(
						'type' => self::type_timestamp,
						'not null' => true,
						'default' => '0000-00-00 00:00:00',
					),
				),
				'primary keys' => array(
					'id' => true,
				),
				'indexes' => array(
					'ContactDates' => array(
						'contact',
						'value',
					),
				),
			),
		));
	}
}
