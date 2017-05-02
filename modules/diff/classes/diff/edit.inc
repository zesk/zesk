<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/diff/classes/diff/edit.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;
/**
 * 
 * @author kent
 *
 */
class Diff_Edit {
	/**
	 * 
	 * @var string
	 */
	const DIFF_INSERT = 'insert';
	/**
	 * 
	 * @var string
	 */
	const DIFF_DELETE = 'delete';
	/**
	 * 
	 * @var string
	 */
	const DIFF_MATCH = 'match';
	
	/**
	 * Operation
	 * 
	 * @var string 
	 */
	public $op;
	
	/**
	 * Offset 
	 * 
	 * @var integer
	 */
	public $off;
	/**
	 * Length
	 * 
	 * @var integer
	 */
	public $len;
	/**
	 * Data which changed
	 * 
	 * @var mixed
	 */
	public $data = null;
	
	/**
	 * 
	 * @param string $op
	 * @param integer $off
	 * @param integer $len
	 * @param mixed $data
	 */
	function __construct($op, $off, $len, $data = null) {
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
	function __toString() {
		return PHP::dump($this->op) . ", " . $this->off . ", " . $this->len . ', ' . PHP::dump($this->data);
	}
}

