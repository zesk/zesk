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
	private $root = null;

	/**
	 *
	 * @var string
	 */
	private $lower = false;

	/**
	 *
	 * @var string
	 */
	private $cleaned = false;

	/**
	 *
	 * @var string
	 */
	private $optimized = false;

	/**
	 *
	 * @var integer
	 */
	public $n_optimized = 0;

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
	 * @param string $word
	 */
	public function add($word): void {
		if ($this->lower) {
			$word = strtolower($word);
		}
		$this->root->add($word);
		$this->cleaned = false;
		$this->optimized = false;
	}

	/**
	 * Clean a trie
	 */
	public function clean() {
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
	 */
	public function optimize() {
		$this->clean();
		if ($this->optimized) {
			return $this;
		}
		while (($optimized = $this->root->optimize()) > 0) {
			$this->n_optimized += $optimized;
		}
		$this->optimized = true;
		return $this;
	}

	/**
	 * Convert to JSON
	 *
	 * @return string
	 */
	public function to_json() {
		return JSON::encode($this->root->to_json());
	}

	/**
	 * Walk the entire trie and call "function" on each node
	 */
	public function walk($function): void {
		$this->root->walk($function, '');
	}
}
