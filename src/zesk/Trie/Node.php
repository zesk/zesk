<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Trie;

class Node
{
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
	 * Create a new node
	 *
	 * @param ?string $word
	 */
	public function __construct(string $word = null)
	{
		$this->next = [];
		if ($word !== null) {
			$this->add($word);
		}
	}

	/**
	 * Create a new node
	 *
	 * @param ?string $word
	 * @return self
	 */
	public static function factory(?string $word = null): self
	{
		return new self($word);
	}

	/**
	 * Does this node represent the end of a word?
	 *
	 * @return bool
	 */
	public function isEnd(): bool
	{
		return array_key_exists('', $this->next);
	}

	/**
	 * Set whether this node represents the end of a word.
	 *
	 * @param bool $set True when end of word.
	 * @return self
	 */
	public function setEnd(bool $set): self
	{
		if ($set) {
			$this->next[''] = 1;
		} else {
			unset($this->next['']);
		}
		return $this;
	}

	/**
	 *
	 * @param string $word
	 * @return Node Added node
	 */
	public function add(string $word): self
	{
		if (strlen($word) === 0) {
			$this->setEnd(true);
			return $this;
		}
		$char = $word[0];
		$remain = substr($word, 1);
		if (!array_key_exists($char, $this->next)) {
			$this->next[$char] = $remain === '' ? 1 : $remain;
			return $this;
		}
		$next = $this->next[$char];
		if (is_string($next)) {
			// Ok, it was simply a completion, so let's convert it into a Node with two entries
			$this->next[$char] = $newNode = self::factory($next);
			$newNode->add($remain);
			return $this;
		}
		if ($next === 1) {
			if ($remain === '') {
				return $this;
			}
			$this->next[$char] = $newNode = self::factory($remain);
			$newNode->add('');
			return $this;
		}
		assert($next instanceof Node);
		$next->add($remain);
		return $this;
	}

	/**
	 * Clean the trie after building to remove unnecessary keys
	 */
	public function clean(): void
	{
		foreach ($this->next as $v) {
			if ($v instanceof Node) {
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
	private function optimizable(): bool
	{
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
	private function merge(string $phrase): int
	{
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
	public function optimize(): int
	{
		$merged = 0;
		if ($this->optimizable()) {
			foreach ($this->next as $k => $v) {
				if ($v instanceof self) {
					if ($v->optimizable()) {
						$merged += $v->merge($k);
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
	public function toJSON(): array
	{
		$json = [];
		foreach ($this->next as $k => $v) {
			if ($v instanceof Node) {
				$json[$k] = $v->toJSON();
			} else {
				$json[$k] = $v;
			}
		}
		return $json;
	}

	public function find(string $word): bool
	{
		if (strlen($word) === 0) {
			if (array_key_exists('', $this->next)) {
				return $this->next[''] === 1;
			}
			return false;
		}
		$char = $word[0];
		if (!array_key_exists($char, $this->next)) {
			return false;
		}
		$next = $this->next[$char];
		$remain = substr($word, 1);
		if ($next === 1) {
			return ($remain === '');
		}
		if (is_string($next)) {
			return $next === $remain;
		}
		assert($next instanceof Node);
		return $next->find($remain);
	}

	/**
	 * Walk trie nodes
	 * @param callable $function
	 * @param string $word Current word state
	 */
	public function walk(callable $function, string $word): void
	{
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
