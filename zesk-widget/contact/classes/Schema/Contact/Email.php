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
						'type' => self::TYPE_ID,
					],
					'contact' => [
						'type' => self::TYPE_OBJECT,
						'not null' => true,
					],
					'label' => [
						'type' => self::TYPE_OBJECT,
						'not null' => false,
					],
					'value' => [
						'not null' => true,
						'size' => 128,
						'sql_type' => 'varchar',
						'type' => self::TYPE_STRING,
					],
					'created' => [
						'not null' => true,
						'default' => '0',
						'type' => self::TYPE_CREATED,
					],
					'modified' => [
						'not null' => true,
						'default' => '0',
						'type' => self::TYPE_MODIFIED,
					],
					'verified' => [
						'not null' => false,
						'default' => 0,
						'type' => self::TYPE_TIMESTAMP,
					],
					'opt_out' => [
						'default' => false,
						'type' => self::TYPE_BOOL,
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
