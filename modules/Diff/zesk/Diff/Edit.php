<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Diff;

use zesk\PHP;

/**
 *
 * @author kent
 *
 */
class Edit {
	/**
	 *
	 * @var string
	 */
	public const DIFF_INSERT = 'insert';

	/**
	 *
	 * @var string
	 */
	public const DIFF_DELETE = 'delete';

	/**
	 *
	 * @var string
	 */
	public const DIFF_MATCH = 'match';

	/**
	 * Operation
	 *
	 * @var string
	 */
	public string $op;

	/**
	 * Offset
	 *
	 * @var integer
	 */
	public int $off;

	/**
	 * Length
	 *
	 * @var integer
	 */
	public int $len;

	/**
	 * Data which changed
	 *
	 * @var mixed
	 */
	public mixed $data = null;

	/**
	 *
	 * @param string $op
	 * @param int $off
	 * @param int $len
	 * @param mixed $data
	 */
	public function __construct(string $op, int $off, int $len, mixed $data = null) {
		$this->op = $op;
		$this->off = $off;
		$this->len = $len;
		$this->data = $data;
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return PHP::dump($this->op) . ', ' . $this->off . ', ' . $this->len . ', ' . PHP::dump($this->data);
	}
}
