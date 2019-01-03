<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Trie/Node.php $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Trie_Node {
	/**
	 *
	 * @var array
	 */
	public $next = array();

	/**
	 *
	 * @param unknown $word
	 */
	public function __construct($word = null) {
		$this->next = array();
		if ($word !== null) {
			$this->add($word);
		}
	}

	/**
	 *
	 * @return boolean
	 */
	public function term() {
		return array_key_exists('$', $this->next);
	}

	/**
	 *
	 * @param string $word
	 */
	public function add($word) {
		if (strlen($word) === 0) {
			$this->next['$'] = 1;
			return;
		}
		$char = $word[0];
		$next = avalue($this->next, $char);
		if (is_string($next)) {
			unset($this->next[$next]);
			$next = $this->next[$char] = new trie_node(substr($next, 1));
			$next->add(substr($word, 1));
		} elseif (is_object($next)) {
			$next->add(substr($word, 1));
		} elseif ($next === 1) {
			$next = $this->next[$char] = new trie_node('$');
			$next->add(substr($word, 1));
		} else {
			$this->next[$word] = 1;
			if (strlen($word) > 1) {
				$this->next[$char] = $word;
			}
		}
	}

	/**
	 * Clean the trie
	 */
	public function clean() {
		// Remove all single letter tags which map to strings
		foreach ($this->next as $k => $v) {
			if (strlen($k) === 1 && is_string($v)) {
				unset($this->next[$k]);
			} elseif (is_object($v)) {
				$v->clean();
			}
			ksort($this->next);
		}
	}

	/**
	 * If only one follow-up node, then we can optimize
	 *
	 * @return boolean
	 */
	private function optimizable() {
		if (count($this->next) === 1) {
			return true;
		}
		if (count($this->next) === 2) {
			foreach ($this->next as $v) {
				if ($v instanceof Trie_Node) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param string $phrase
	 * @return number Number of nodes merged
	 */
	private function merge($phrase) {
		$node = $this->next[$phrase];
		unset($this->next[$phrase]);
		$nmerged = 0;
		foreach ($node->next as $k => $v) {
			if ($k === '$') {
				$this->next[$phrase] = 1;
			} else {
				$this->next[$phrase . $k] = $v;
			}
			$nmerged++;
		}
		return $nmerged;
	}

	/**
	 * Optimize the trie structure
	 *
	 * @return number
	 */
	public function optimize() {
		$merged = 0;
		if ($this->optimizable()) {
			foreach ($this->next as $k => $v) {
				if ($v instanceof Trie_Node) {
					if ($v->optimizable()) {
						$merged += $this->merge($k);
					} else {
						$merged += $v->optimize();
					}
				}
			}
		} else {
			foreach ($this->next as $k => $v) {
				if ($v instanceof Trie_Node) {
					$merged += $v->optimize();
				}
			}
		}
		return $merged;
	}

	/**
	 * Convert to JSON
	 *
	 * @return string[]
	 */
	public function to_json() {
		$json = array();
		foreach ($this->next as $k => $v) {
			if (is_object($v)) {
				$json[$k] = $v->to_json();
			} else {
				$json[$k] = $v;
			}
		}
		return $json;
	}

	/**
	 * Walk trie nodes
	 * @param callable $function
	 * @param string $word Current word state
	 */
	public function walk($function, $word) {
		foreach ($this->next as $k => $v) {
			if ($v === 1) {
				if ($k === '$') {
					$k = '';
				}
				call_user_func($function, "$word$k");
			} elseif (is_object($v)) {
				$v->walk($function, "$word$k");
			}
		}
	}
}
