<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Trie;

class Node {
	/**
	 * The next array is keyed by single letters and strings. When the key is a single letter, it contains:
	 *
	 * - additional Nodes
	 * - the number 1 - meaning it's a completion letter
	 *
	 * When the key is a string of more than 1 letter or is the character '', it ALWAYS has the value 1
	 *
	 * - there is some duplication in that adding a string will add:
	 *
	 * ['a'] = 'pple';
	 * ['apple'] = 1;
	 * ['b'] = new Node
	 * initially
	 *
	 * @var array
	 */
	public array $next = [];

	/**
	 *
	 * @var boolean
	 */
	private bool $end_of_word = false;

	/**
	 * Create a new node
	 *
	 * @param string $word
	 * @param boolean $endOfWord End of word flag
	 */
	public function __construct(string $word = '', bool $endOfWord = false) {
		$this->next = [];
		if ($word !== '') {
			$this->add($word);
		}
		$this->setEndOfWord($endOfWord);
	}

	/**
	 * Create a new node
	 *
	 * @param string $word
	 * @param boolean $endOfWord End of word flag
	 * @return self
	 */
	public static function factory(string $word, bool $endOfWord = false): self {
		return new self($word, $endOfWord);
	}

	/**
	 * Does this node represent the end of a word?
	 *
	 * @return bool
	 */
	public function isEndOfWord(): bool {
		return $this->end_of_word;
	}

	/**
	 * Set whether this node represents the end of a word.
	 *
	 * @param bool $set True when end of word.
	 * @return self
	 */
	public function setEndOfWord(bool $set): self {
		$this->end_of_word = $set;
		return $this;
	}

	/**
	 *
	 * @param string $word
	 * @return Node Added node
	 */
	public function add(string $word): self {
		if (strlen($word) === 0) {
			$this->setEndOfWord(true);
			return $this;
		}
		$char = $word[0];
		$remain = substr($word, 1);
		$next = $this->next[$char] ?? null;
		if (is_string($next)) {
			// Ok, it was simply a completion, so let's convert it into a Node with two entries
			unset($this->next[$word]);
			$this->next[$char] = self::factory($next)->add($remain);
		} elseif ($next instanceof Node) {
			// It's a Node, traverse one letter deeper in our word
			$next->add($remain);
		} elseif ($next === 1) {
			// This represents a completion for this word, convert it into another Node to package both
			$this->next[$char] = $remain === '' ? 1 : self::factory($remain);
		} else {
			// Nothing found. So make this word a completion at this point, and add a single character node below
			$this->next[$word] = 1;
			if (strlen($word) > 1) {
				$this->next[$char] = $remain;
			}
		}
		return $this;
	}

	/**
	 * Clean the trie after building to remove unnecessary keys
	 */
	public function clean(): void {
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
	private function optimizable(): bool {
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
	private function merge(string $phrase): int {
		assert(array_key_exists($phrase, $this->next));
		$node = $this->next[$phrase];
		unset($this->next[$phrase]);
		assert($node instanceof self);
		$numberMerged = 0;
		foreach ($node->next as $k => $v) {
			if ($k === '') {
				$this->next[$phrase] = 1;
			} else {
				$this->next[$phrase . $k] = $v;
			}
			$numberMerged++;
		}
		return $numberMerged;
	}

	/**
	 * Optimize the trie structure
	 *
	 * @return number
	 */
	public function optimize(): int {
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
			foreach ($this->next as $v) {
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
	 * @return array
	 */
	public function toJSON(): array {
		$json = [];
		foreach ($this->next as $k => $v) {
			if ($v instanceof Node) {
				$json[$k] = $v->toJSON();
			} elseif (strlen($k) > 1 && array_key_exists($k[0], $this->next) && $this->next[$k[0]] instanceof self
				&& $this->next[$k[0]]->find(substr($k, 1))) {
				continue;
			} else {
				$json[$k] = $v;
			}
		}
		if ($this->isEndOfWord()) {
			$json[''] = 1;
		}
		return $json;
	}

	public function find(string $word): bool {
		if (strlen($word) === 0 && $this->isEndOfWord()) {
			return true;
		}
		if (array_key_exists($word, $this->next) && $this->next[$word] === 1) {
			return true;
		}
		$first = $word[0];
		$remain = substr($word, 1);
		$next = $this->next[$first] ?? null;
		if ($next instanceof self) {
			return $next->find($remain);
		} elseif (is_string($next)) {
			return $next === $remain;
		}
		return false;
	}

	/**
	 * Walk trie nodes
	 * @param callable $function
	 * @param string $word Current word state
	 */
	public function walk(callable $function, string $word): void {
		foreach ($this->next as $k => $v) {
			if (strlen($k) > 1 && array_key_exists($k[0], $this->next)) {
				continue;
			}
			if ($v === 1) {
				call_user_func($function, "$word$k");
			} elseif ($v instanceof Node) {
				$v->walk($function, "$word$k");
			} elseif (is_string($v)) {
				call_user_func($function, "$word$k$v");
			}
		}
	}
}
