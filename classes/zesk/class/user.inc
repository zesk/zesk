<?php

namespace zesk;

/**
 * @see Class_User
 * @author kent
 *
 */
class Class_User extends Class_Object {
	/**
	 *
	 * @var string
	 */
	public $id_column = "id";

	/**
	 * Overwrite the field to change the login column
	 *
	 * @var string
	 */
	public $column_login = "login_email";
	/**
	 * Overwrite the field to change the login column
	 *
	 * @var string
	 */
	public $column_email = "login_email";

	/**
	 * Overwrite the field to change the password column
	 *
	 * @var string
	 */
	public $column_password = "login_password";
}
