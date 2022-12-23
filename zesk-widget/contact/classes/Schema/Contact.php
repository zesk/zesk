<?php declare(strict_types=1);
namespace zesk;

class Schema_Contact extends ORM_Schema {
	public function schema() {
		return [
			'{table}' => [
				'columns' => [
					'id' => [
						'not null' => true,
						'increment' => true,
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'hash' => [
						'not null' => true,
						'size' => 16,
						'type' => 'binary(16)',
						'sql_type' => 'binary(16)',
					],
					'account' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'user' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'keywords' => [
						'type' => 'text',
						'sql_type' => 'text',
					],
					'person' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'email' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'address' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'phone' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'url' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'notes' => [
						'type' => 'text',
						'sql_type' => 'text',
					],
					'created' => [
						'not null' => true,
						'default' => '0',
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
					],
					'modified' => [
						'not null' => true,
						'default' => '0',
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
					],
					'connector' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'verified' => [
						'type' => 'timestamp',
						'sql_type' => 'timestamp',
					],
					'verifier' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
					'duplicate' => [
						'unsigned' => true,
						'type' => 'integer',
						'sql_type' => 'integer',
					],
				],
				'primary keys' => [
					'id',
				],
				'indexes' => [
					'e' => [
						'email',
					],
					'a' => [
						'address',
					],
					'p' => [
						'phone',
					],
					'v' => [
						'verifier',
					],
				],
			],
		];
	}
}
