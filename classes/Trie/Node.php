<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Trie/Node.php $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
namespace zesk\Trie;

/**
 *
 * @author kent
 *
 */
class Node {
	/**
	 * The next array is keyed by single letters and strings. When the key is a single letter, it contains:
	 *
	 * - additional Nodes
	 * - the number 1 - meaning it's a completion letter
	 *
	 * When the key is a string of more than 1 letter or is the character '$', it ALWAYS has the value 1
	 *
	 * - there is some duplication in that adding a string will add:
	 *
	 * ['a'] = 'pple';
	 * ['apple'] = 1;
	 * ['b'] = new Node
	 * initialiy
	 *
	 * @var array
	 */
	public $next = array();

	/**
	 *
	 * @var boolean
	 */
	private $end_of_word = false;

	/**
	 * Create a new node
	 *
	 * @param string $word
	 * @param boolean $eow End of word flag
	 */
	public function __construct($word = null, $eow = false) {
		$this->next = array();
		if ($word !== null) {
			$this->add($word);
		}
		$this->term($eow);
	}

	/**
	 * Create a new node
	 *
	 * @param string $word
	 * @param boolean $eow End of word flag
	 */
	public static function factory($word, $eow = false) {
		return new self($word, $eow);
	}

	/**
	 * Does this node represent the end of a word? (getter/setter)
	 *
	 * @return boolean|set
	 */
	public function term($set = null) {
		if ($set !== null) {
			$this->end_of_word = boolval($set);
			return $this;
		}
		return $this->end_of_word;
	}

	/**
	 *
	 * @param string $word
	 */
	public function add($word) {
		if (strlen($word) === 0) {
			$this->end_of_word = true;
			return $this;
		}
		$char = $word[0];
		$next = avalue($this->next, $char);
		if (is_string($next)) {
			// Ok, it was simply a completion, so let's convert it into a Node with two entries
			unset($this->next[$word]);
			$next = $this->next[$char] = self::factory($next)->add(substr($word, 1));
		} elseif ($next instanceof Node) {
			// It's a Node, traverse one letter deeper in our word
			$next->add(substr($word, 1));
		} elseif ($next === 1) {
			// This represents a completion for this word, convert it into another Node to package both
			$next = $this->next[$char] = self::factory(substr($word, 1), true);
		} else {
			// Nothing found. So make this word a completion at this point, and add a single character node below
			$this->next[$word] = 1;
			if (strlen($word) > 1) {
				$this->next[$char] = substr($word, 1);
			}
		}
		return $this;
	}

	/**
	 * Clean the trie after building to remove unnecessary keys
	 */
	public function clean() {
		// Remove all single letter tags which map to strings
		foreach ($this->next as $k => $v) {
			if (strlen($k) === 1 && is_string($v)) {
				unset($this->next[$k]);
			} elseif ($v instanceof Node) {
				$v->clean();
			}
			ksort($this->next);
		}
	}

	/**
	 * If only one follow-up node, then we can optimize. If everything is strings we can optimize, too.
	 *
	 * @return boolean
	 */
	private function optimizable() {
		if (count($this->next) === 1) {
			return true;
		}
		if (count($this->next) === 2) {
			foreach ($this->next as $v) {
				if ($v instanceof self) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param string $phrase MUST be associated with a value in ->next which is a Node
	 * @return number Number of nodes merged
	 */
	private function merge($phrase) {
		// $phrase MUST
		$node = $this->next[$phrase];
		unset($this->next[$phrase]);
		assert($node instanceof self);
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
				if ($v instanceof self) {
					if ($v->optimizable()) {
						$merged += $this->merge($k);
					} else {
						$merged += $v->optimize();
					}
				}
			}
		} else {
			foreach ($this->next as $k => $v) {
				if ($v instanceof self) {
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
			if ($v instanceof Node) {
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
			} elseif ($v instanceof Node) {
				$v->walk($function, "$word$k");
			}
		}
	}
}
