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
class Schema_Contact_Email extends ORM_Schema {
	public function schema() {
		return [
			'{table}' => [
				'columns' => [
					'id' => [
						'type' => self::type_id,
					],
					'contact' => [
						'type' => self::type_object,
						'not null' => true,
					],
					'label' => [
						'type' => self::type_object,
						'not null' => false,
					],
					'value' => [
						'not null' => true,
						'size' => 128,
						'sql_type' => 'varchar',
						'type' => self::type_string,
					],
					'created' => [
						'not null' => true,
						'default' => '0',
						'type' => self::type_created,
					],
					'modified' => [
						'not null' => true,
						'default' => '0',
						'type' => self::type_modified,
					],
					'verified' => [
						'not null' => false,
						'default' => 0,
						'type' => self::type_timestamp,
					],
					'opt_out' => [
						'default' => false,
						'type' => self::type_boolean,
					],
				],
				'primary keys' => [
					'id' => true,
				],
				'indexes' => [
					'contact' => [
						'contact',
					],
				],
			],
		];
	}
}
