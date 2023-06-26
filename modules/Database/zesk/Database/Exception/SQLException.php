<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database\Exception;

use zesk\Database\Base;
use zesk\Database\Exception;
use Throwable;

/**
 *
 * @author kent
 *
 */
class SQLException extends Exception {
	/**
	 *
	 * @var string
	 */
	public string $sql = '';

	/**
	 * @param Base $db
	 * @param string $sql
	 * @param string $message
	 * @param array $arguments
	 * @param int $errno
	 * @param Throwable|null $previous
	 */
	public function __construct(Base $db, string $sql = '', string $message = '', array $arguments = [], int $errno = 0, \Throwable $previous = null) {
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
