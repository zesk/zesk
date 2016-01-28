<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/diff/classes/diff/edit.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
class Diff_Edit {

	const DIFF_INSERT = 'insert';

	const DIFF_DELETE = 'delete';

	const DIFF_MATCH = 'match';

	public $op;

	public $off;

	public $len;

	public $data = null;

	function __construct($op, $off, $len, $data = null) {
		$this->op = $op;
		$this->off = $off;
		$this->len = $len;
		$this->data = $data;
	}

	function __toString() {
		return php::dump($this->op) . ", " . $this->off . ", " . $this->len . ', ' . php::dump($this->data);
	}
}

