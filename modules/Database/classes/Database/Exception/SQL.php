<?php
declare(strict_types=1);
/**
 * $Id$
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Throwable;

/**
 *
 * @author kent
 *
 */
class Database_Exception_SQL extends Database_Exception {
	/**
	 *
	 * @var string
	 */
	public string $sql = '';

	/**
	 * @param Database $db
	 * @param string $sql
	 * @param string $message
	 * @param array $arguments
	 * @param int $errno
	 * @param Throwable|null $previous
	 */
	public function __construct(Database $db, string $sql = '', string $message = '', array $arguments = [], int $errno = 0, \Throwable $previous = null) {
		$this->sql = $sql;

		$message = "Message: $message\nDatabase: " . $db->codeName() . "\nSQL: " . rtrim($this->sql) . "\n";
		parent::__construct($db, $message, $arguments, $errno, $previous);
	}

	/**
	 *
	 * @return string
	 */
	public function sql(): string {
		return $this->sql;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see POPException::__toString()
	 */
	public function __toString(): string {
		$result = parent::__toString();
		$result .= 'Error Number: ' . $this->getCode() . "\n\n";
		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see zesk\Exception::variables()
	 */
	public function variables(): array {
		return [
			'errno' => $this->getCode(), 'sql' => $this->sql(),
		] + parent::variables();
	}
}
