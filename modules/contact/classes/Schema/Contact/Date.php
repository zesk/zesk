<?php declare(strict_types=1);
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
		return $this->map([
			'{table}' => [
				'columns' => [
					'id' => [
						'type' => self::type_id,
					],
					'contact' => [
						'type' => self::type_object,
					],
					'label' => [
						'type' => self::type_object,
					],
					'value' => [
						'not null' => true,
						'type' => self::type_date,
					],
					'created' => [
						'not null' => true,
						'type' => self::type_timestamp,
						'default' => '0000-00-00 00:00:00',
					],
					'modified' => [
						'type' => self::type_timestamp,
						'not null' => true,
						'default' => '0000-00-00 00:00:00',
					],
				],
				'primary keys' => [
					'id' => true,
				],
				'indexes' => [
					'ContactDates' => [
						'contact',
						'value',
					],
				],
			],
		]);
	}
}
