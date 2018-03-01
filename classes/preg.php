<?php

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Usage:
 * <code>
 * foreach (($preg = preg::matches('/[a-zA-Z]+ /', $string)) as $match) {
 *     $new_string = ... new string replacement ...;
 *     $string = $preg->replace_current($new_string);
 * }
 * </code>
 *
 * Replaces one at a time using offsets to avoid find/replacing within the entire string.
 * Closer to Perl pattern matching/replacing functionality.
 *
 * @author kent
 *
 */
class preg implements \ArrayAccess, \Iterator {
	
	/**
	 *
	 * @var string
	 */
	private $pattern = null;
	/**
	 *
	 * @var string
	 */
	private $text = null;
	/**
	 *
	 * @var integer
	 */
	private $flag = null;
	/**
	 *
	 * @var mixed
	 */
	private $current = null;
	/**
	 *
	 * @var integer
	 */
	private $offset = null;
	/**
	 *
	 * @var array
	 */
	private $matches = null;
	/**
	 *
	 * @var array
	 */
	private $offsets = array();
	
	/**
	 * 
	 */
	private function __construct($pattern, $text, $flag = PREG_SET_ORDER, $offset) {
		$this->pattern = $pattern;
		$this->text = $text;
		$this->flag = $flag;
		$this->offset = $offset;
		if ($flag === null) {
			$flag = PREG_SET_ORDER;
		}
		$flag |= PREG_OFFSET_CAPTURE;
		if (!preg_match_all($pattern, $text, $this->matches, $flag, $offset)) {
			$this->matches = array();
		}
		/**
		 * Make results consistent with non-offset-capture and store in parallel array
		 * to use when needed.
		 */
		foreach ($this->matches as $match_index => $match_capture) {
			foreach ($match_capture as $match_capture_index => $match_and_offset) {
				$this->matches[$match_index][$match_capture_index] = $match_and_offset[0];
				$this->offsets[$match_index][$match_capture_index] = $match_and_offset[1];
			}
		}
	}
	
	/**
	 * 
	 */
	public static function matches($pattern, $text, $flag = PREG_SET_ORDER, $offset = 0) {
		return new preg($pattern, $text, $flag, $offset);
	}
	
	/**
	 * 
	 */
	public function replace_current($replace) {
		$key = $this->key();
		$offset = $this->offsets[$key][0];
		$match = $this->matches[$key][0];
		$match_len = strlen($match);
		$this->text = substr($this->text, 0, $offset) . $replace . substr($this->text, $offset + $match_len);
		
		// We've changed our text, adjust all later offsets - used above
		$delta = strlen($replace) - $match_len;
		while (++$key <= count($this->offsets) - 1) {
			$this->offsets[$key][0] += $delta;
		}
		
		return $this->text;
	}
	/**
	 * @param offset
	 */
	public function offsetGet($offset) {
		return avalue($this->matches, $offset);
	}
	/**
	 * @param offset
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->matches);
	}
	/**
	 * @param offset
	 * @param value
	 */
	public function offsetSet($offset, $value) {
		$this->matches[$offset] = $value;
	}
	
	/**
	 * @param offset
	 */
	public function offsetUnset($offset) {
		unset($this->matches[$offset]);
	}
	public function current() {
		return current($this->matches);
	}
	public function next() {
		next($this->matches);
	}
	public function key() {
		return key($this->matches);
	}
	public function valid() {
		return $this->key() !== null;
	}
	public function rewind() {
		reset($this->matches);
	}
}
