<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 *            Created on Tue Apr 06 10:53:22 EDT 2010 10:53:22
 */
namespace zesk;

class Database_Exception extends Exception {
	/**
	 *
	 * @var Database
	 */
	public $database = null;

	/**
	 *
	 * @param Database $database
	 * @param string $message
	 * @param array $arguments
	 * @param mixed|null $code
	 * @param Exception|null $previous
	 * @return void
	 */
	public function __construct(Database $database, $message, $arguments = [], $code = null, \Exception $previous = null) {
		$this->database = $database;
		parent::__construct($message, $arguments, $code, $previous);
	}

	/**
	 *
	 * @return Database
	 */
	public function database() {
		return $this->database;
	}
}
