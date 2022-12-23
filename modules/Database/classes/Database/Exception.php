<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Tue Apr 06 10:53:22 EDT 2010 10:53:22
 */

namespace zesk;

class Database_Exception extends Exception {
	/**
	 *
	 * @var Database
	 */
	public Database $database;

	/**
	 *
	 * @param Database $database
	 * @param string $message
	 * @param array $arguments
	 * @param mixed|null $code
	 * @param Exception|null $previous
	 * @return void
	 */
	public function __construct(Database $database, string $message, array $arguments = [], int $code = 0, \Exception $previous = null) {
		$this->database = $database;
		parent::__construct($message, $arguments, $code, $previous);
	}

	/**
	 *
	 * @return Database
	 */
	public function database(): Database {
		return $this->database;
	}
}
