<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/writer.inc $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * XML_Writer
 *
 * Long description
 *
 * @package zesk
 * @subpackage tools
 */
class XML_Writer implements XML_Writer_Interface {

	private $Root;

	private $Path;

	private $Top;

	private $Dirty;

	function __construct() {
		//		parent::__construct();
		$this->Root = new XML_Node("*");
		$this->Path = array();
		$this->Top = $this->Root;
		$this->Dirty = false;
	}

	function __sleep() {
		//		$fields = parent::__sleep();
		$fields[] = "Root";
		return $fields;
	}

	function __wakeup() {
		$this->Path = array();
		$this->Top = false;
		$this->Dirty = false;
	}

	function push($tag, $attributes = false, $content = "") {
		$tag = strtolower($tag);
		$this->Top = $this->add($tag, $attributes, $content);
		
		$this->Path[] = $tag;
		$this->Dirty = true;
		
		return $this->Top;
	}

	private function _find($path) {
		$cur = $this->Root;
		foreach ($path as $ptag) {
			assert('$cur instanceof XML_Node');
			$cur = $cur->find($ptag);
		}
		return $cur;
	}

	private function _top() {
		return $this->_find($this->Path);
	}

	function top() {
		if (empty($this->Top)) {
			$this->Top = $this->_top();
		}
		return $this->Top;
	}

	function add($tag, $attributes = false, $content = false) {
		$node = new XML_Node(strtolower($tag), $attributes, $content);
		
		$this->Top->add($node);
		
		$this->Dirty = true;
		
		return $node;
	}

	function addContent($content = "") {
		if (empty($content)) {
			return $this->Top;
		}
		$this->Top->add($content);
		
		$this->Dirty = true;
		
		return $this->Top;
	}

	function pop($tag = false) {
		if (count($this->Path) == 0) {
			return false;
		}
		$oldTop = $this->_top();
		$poptag = array_pop($this->Path);
		if (($tag !== false) && ($poptag !== strtolower($tag))) {
			throw new Exception_Semantics("XML_Writer::pop($tag) not correct tag \"$poptag\"");
		}
		$this->Top = $this->_top();
		return $oldTop;
	}

	public function find($path = false, $create = true) {
		if (is_string($path)) {
			$path = explode(".", $path);
		} else if (!is_array($path)) {
			throw new Exception_Parameter("XML_Writer::find($path): not a string or array: " . gettype($path));
		}
		$node = $this->Root;
		$ks = array_keys($path);
		if (count($ks) === 0) {
			throw new Exception_Parameter("XML_Writer::find($path): No path passed");
		}
		if (is_int($ks[0])) {
			foreach ($path as $tag) {
				$newNode = $node->find($tag);
				if ($newNode === false) {
					if (!$create) {
						return false;
					}
					$newNode = new XML_Node($tag, false);
					$node->add($newNode);
				}
				$node = $newNode;
			}
		} else {
			foreach ($path as $tag => $attrs) {
				$newNode = $node->find($tag);
				if ($newNode === false) {
					if (!$create) {
						return false;
					}
					$newNode = new XML_Node($tag, $attrs);
					$node->add($newNode);
				}
				$node = $newNode;
			}
		}
		return $node;
	}

	function output($options = false) {
		$xml_header = false;
		$singles = false;
		if (is_array($options)) {
			$options = new Options($options);
			$xml_header = $options->option_bool("xml_header", false);
			$singles = $options->option_bool("xml_singles", false);
		}
		$lines = array();
		if ($xml_header) {
			$lines[] = '<?xml version="1.0" standalone="no"?>';
		}
		$children = $this->Root->children();
		foreach ($children as $child) {
			$lines[] = $child->output(0, $singles);
		}
		return implode("\n", $lines);
	}
}

