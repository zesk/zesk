<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Trie.php $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Trie extends Options {
	/**
	 * 
	 * @var Trie_Node
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
	 * @param unknown $options
	 */
	function __construct(array $options = array()) {
		parent::__construct($options);
		$this->lower = $this->option_bool('lower');
		$this->root = new Trie_Node();
	}
	
	/**
	 * @param string $word
	 */
	function add($word) {
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
	function clean() {
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
	function optimize() {
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
	function to_json() {
		return JSON::encode($this->root->to_json());
	}
	
	/**
	 * Walk the entire trie and call "function" on each node
	 */
	function walk($function) {
		$this->root->walk($function, '');
	}
}
