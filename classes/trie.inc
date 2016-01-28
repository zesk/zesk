<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/trie.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

class Trie extends Options {
	private $root = null;

	private $lower = false;

	private $cleaned = false;

	private $optimized = false;

	public $n_optimized = 0;

	function __construct($options=null) {
		parent::__construct($options);
		$this->lower = $this->option_bool('lower');
		$this->root = new Trie_Node();
	}

	function add($word) {
		if ($this->lower) {
			$word = strtolower($word);
		}
		$this->root->add($word);
		$this->cleaned = false;
		$this->optimized = false;
	}

	function clean() {
		if ($this->cleaned) {
			return $this;
		}
		$this->optimized = false;
		$this->root->clean();
		$this->cleaned = true;
		return $this;
	}

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

	function to_json() {
		return json::encode($this->root->to_json());
	}

	function walk($function) {
		$this->root->walk($function, '');
	}
}
