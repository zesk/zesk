<?php
namespace zesk;

/**
 * @see Class_User
 * @author kent
 *
 */
class Class_User extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 * Name column
	 *
	 * @var string
	 */
	public $name_column = "name_first,name_last";
	
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
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Class_ORM::initialize()
	 */
	protected function initialize() {
		$this->column_types[$this->id_column] = self::type_id;
		if ($this->column_login && !isset($this->column_types[$this->column_login])) {
			$this->column_types[$this->column_login] = self::type_string;
		}
		if ($this->column_email && !isset($this->column_types[$this->column_email])) {
			$this->column_types[$this->column_email] = self::type_string;
		}
		if ($this->column_password && !isset($this->column_types[$this->column_password])) {
			$this->column_types[$this->column_password] = self::type_string;
		}
	}
}
