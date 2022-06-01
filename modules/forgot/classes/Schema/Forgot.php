<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/Schema/Forgot.php $
 * @package zesk
 * @subpackage forgot
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
class Schema_Forgot extends ORM_Schema {
	/**
	 * @todo if updated default value isn't 0000-00-00 00:00:00 then Forgot schema is always updated. Need to check.
	 *
	 * {@inheritDoc}
	 * @see ORM_Schema::schema()
	 */
	public function schema(): array {
		$result = [
			'{table}' => [
				'columns' => [
					'id' => [
						'type' => self::type_id,
						'previous_name' => 'ID',
					],
					'login' => [
						'type' => self::type_string,
						'not null' => false,
						'size' => 128,
					],
					'user' => [
						'type' => self::type_object,
						'class' => User::class,
						'not null' => true,
						'previous_name' => 'User',
					],
					'session' => [
						'type' => self::type_object,
						'class' => 'zesk\\Session',
						'not null' => true,
						'previous_name' => 'Session',
					],
					'code' => [
						'type' => self::type_binary, //'varbinary',
						'size' => 16,
						'not null' => true,
						'previous_name' => 'Code',
					],
					'created' => [
						'type' => self::type_timestamp,
						'not null' => true,
						'default' => 'CURRENT_TIMESTAMP',
						'previous_name' => 'Created',
					],
					'updated' => [
						'type' => self::type_timestamp,
						'not null' => false,
						'previous_name' => 'Updated',
					],
				],
				'indexes' => [
					'user' => [
						'user',
					],
					'session' => [
						'session',
					],
				],
				'unique keys' => [
					'code' => [
						'code',
					],
				],
				'primary keys' => [
					'id',
				],
			],
		];
		return $this->map($result);
	}
}
