<?php
namespace zesk;

/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/Schema/Forgot.php $
 * @package zesk
 * @subpackage forgot
 * @author kent
 * @copyright &copy; 2014 Market Acumen, Inc.
 */
class Schema_Forgot extends ORM_Schema {
	/**
	 * @todo if updated default value isn't 0000-00-00 00:00:00 then Forgot schema is always updated. Need to check.
	 *
	 * {@inheritDoc}
	 * @see ORM_Schema::schema()
	 */
	function schema() {
		$result = array(
			'{table}' => array(
				'columns' => array(
					'id' => array(
						'type' => self::type_id,
						'previous_name' => 'ID'
					),
					'login' => array(
						'type' => self::type_string,
						'not null' => false,
						'size' => 128
					),
					'user' => array(
						'type' => self::type_object,
						'class' => User::class,
						'not null' => true,
						'previous_name' => 'User'
					),
					'session' => array(
						'type' => self::type_object,
						'class' => 'zesk\\Session',
						'not null' => true,
						'previous_name' => 'Session'
					),
					'code' => array(
						'type' => self::type_binary, //'varbinary',
						'size' => 16,
						'not null' => true,
						'previous_name' => 'Code'
					),
					'created' => array(
						'type' => self::type_timestamp,
						'not null' => true,
						'default' => 'CURRENT_TIMESTAMP',
						'previous_name' => 'Created'
					),
					'updated' => array(
						'type' => self::type_timestamp,
						'not null' => false,
						'previous_name' => 'Updated'
					)
				),
				'indexes' => array(
					'user' => array(
						'user'
					),
					'session' => array(
						'session'
					)
				),
				'unique keys' => array(
					'code' => array(
						'code'
					)
				),
				'primary keys' => array(
					'id'
				)
			)
		);
		return $this->map($result);
	}
}

