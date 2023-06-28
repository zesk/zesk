<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Exception\ConfigurationException;

/**
 * @see Class_User
 * @author kent
 *
 */
class Class_User extends Class_Base
{
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

	public array $find_keys = [
		'login_email',
	];

	/**
	 * Allowable hash methods
	 *
	 * @see hash
	 * @var array
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
	 * @return void
	 * @throws ConfigurationException
	 */
	protected function initialize(): void
	{
		$this->column_types[$this->id_column] = self::TYPE_ID;
		if ($this->column_login && !isset($this->column_types[$this->column_login])) {
			$this->column_types[$this->column_login] = self::TYPE_STRING;
		}
		if ($this->column_email && !isset($this->column_types[$this->column_email])) {
			$this->column_types[$this->column_email] = self::TYPE_STRING;
		}
		if ($this->column_password && !isset($this->column_types[$this->column_password])) {
			$this->column_types[$this->column_password] = self::TYPE_STRING;
		}
		if ($this->hasOption('default_hash_method')) {
			$this->default_hash_method = $this->option('default_hash_method');
		}
		$this->default_hash_method = strtolower($this->default_hash_method);
		if ($this->hasOption('allowed_hash_methods')) {
			$this->allowed_hash_methods = $this->optionArray('allowed_hash_methods');
		}
		$this->allowed_hash_methods = ArrayTools::changeValueCase($this->allowed_hash_methods);
		$algorithms = ArrayTools::valuesFlipCopy(hash_algos());
		foreach ($this->allowed_hash_methods as $index => $name) {
			if (!isset($algorithms[$name])) {
				$this->application->logger->warning('Algorithm {algorithm} is not found in {algorithms}, removing', [
					'algorithm' => $name,
					'algorithms' => array_values($algorithms),
				]);
				unset($this->allowed_hash_methods[$index]);
			}
		}
		if (!in_array($this->default_hash_method, $this->allowed_hash_methods)) {
			throw new ConfigurationException([
				__CLASS__ . '::allowed_hash_methods',
				__CLASS__ . '::default_hash_method',
			], 'Default method {item} does not exist in list {list}', [
				'item' => $this->default_hash_method,
				'list' => $this->allowed_hash_methods,
			]);
		}
	}
}
