<?php declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Trie.php $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Trie\Node;

/**
 * A trie is a tree which is keyed on letters in words, efficient for storing dictionaries which basically just need to know if X is a valid word or not.
 *
 * This trie structure
 * @author kent
 *
 */
class Trie extends Options {
	/**
	 *
	 * @var Node
	 */
	private Node $root;

	/**
	 *
	 * @var bool
	 */
	private bool $lower;

	/**
	 *
	 * @var bool
	 */
	private bool $cleaned = false;

	/**
	 *
	 * @var bool
	 */
	private bool $optimized = false;

	/**
	 *
	 * @var integer
	 */
	public int $numberOptimized = 0;

	/**
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->lower = $this->optionBool('lower');
		$this->root = new Node();
	}

	/**
	 * Add a word
	 * @param string $word
	 */
	public function add(string $word): void {
		if ($this->lower) {
			$word = strtolower($word);
		}
		$this->root->add($word);
		$this->cleaned = false;
		$this->optimized = false;
	}

	/**
	 * Clean a trie
	 * @return $this
	 */
	public function clean(): self {
		if ($this->cleaned) {
			return $this;
		}
		$this->optimized = false;
		$this->root->clean();
		$this->cleaned = true;
		return $this;
	}

	/**
	 * Optimize trie
	 * @return int
	 */
	public function optimize(): int {
		$this->clean();
		if ($this->optimized) {
			return $this->numberOptimized;
		}
		$this->numberOptimized = 0;
		while (($optimized = $this->root->optimize()) > 0) {
			$this->numberOptimized += $optimized;
		}
		$this->optimized = true;
		return $this->numberOptimized;
	}

	/**
	 * Convert to a structure which can be output as JSON
	 *
	 * @return array
	 */
	public function toJSON(): array {
		return $this->root->toJSON();
	}

	/**
	 * Walk the entire trie and call "function" on each node
	 */
	public function walk(callable $function): void {
		$this->root->walk($function, '');
	}
}
