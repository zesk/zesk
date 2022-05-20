<?php
declare(strict_types=1);

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
	public string $id_column = 'id';

	/**
	 * Name column
	 *
	 * @var string
	 */
	public string $name_column = 'name_first,name_last';

	/**
	 * Overwrite the field to change the login column
	 *
	 * @var string
	 */
	public string $column_login = 'login_email';

	/**
	 * Overwrite the field to change the login column
	 *
	 * @var string
	 */
	public string $column_email = 'login_email';

	/**
	 * Overwrite the field to change the password column
	 *
	 * @var string
	 */
	public string $column_password = 'login_password';

	/**
	 * Do we store it as a hex string (e.g. d41d8cd98f00b204e9800998ecf8427e or the binary version of that)
	 *
	 * @var bool
	 */
	public bool $column_password_is_binary = false;

	/**
	 * The column which contains the password hash method (e.g. "md5")
	 *
	 * @var string
	 */
	public string $column_hash_method = 'hash_method';

	/**
	 * Method used to generate the hash
	 *
	 * @var string
	 */
	public string $default_hash_method = 'md5';

	/**
	 * Allowable hash methods
	 *
	 * @see hash
	 * @var string
	 */
	public array $allowed_hash_methods = [
		'md5',
		'sha1',
		'sha512',
		'sha256',
		'ripemd128',
		'ripemd160',
		'ripemd320',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Class_ORM::initialize()
	 */
	protected function initialize(): void {
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
		if ($this->hasOption('default_hash_method')) {
			$this->default_hash_method = $this->option('default_hash_method');
		}
		$this->default_hash_method = strtolower($this->default_hash_method);
		if ($this->hasOption('allowed_hash_methods')) {
			$this->allowed_hash_methods = $this->option_array('allowed_hash_methods');
		}
		$this->allowed_hash_methods = array_filter($this->allowed_hash_methods, 'strtolower');
		$algos = ArrayTools::flip_copy(hash_algos(), true);
		foreach ($this->allowed_hash_methods as $index => $name) {
			if (!isset($algos[$name])) {
				$this->application->logger->warning('Algorithm {algo} is not found in {algos}, removing', [
					'algo' => $name,
					'algos' => array_values($algos),
				]);
				unset($this->allowed_hash_methods[$index]);
			}
		}
		if (!in_array($this->default_hash_method, $this->allowed_hash_methods)) {
			throw new Exception_Configuration([
				__CLASS__ . '::allowed_hash_methods',
				__CLASS__ . '::default_hash_method',
			], 'Default method {item} does not exist in list {list}', [
				'item' => $this->default_hash_method,
				'list' => $this->allowed_hash_methods,
			]);
		}
	}
}
