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
						'type' => self::TYPE_ID,
					],
					'contact' => [
						'type' => self::TYPE_OBJECT,
					],
					'label' => [
						'type' => self::TYPE_OBJECT,
					],
					'value' => [
						'not null' => true,
						'type' => self::TYPE_DATE,
					],
					'created' => [
						'not null' => true,
						'type' => self::TYPE_TIMESTAMP,
						'default' => '0000-00-00 00:00:00',
					],
					'modified' => [
						'type' => self::TYPE_TIMESTAMP,
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
