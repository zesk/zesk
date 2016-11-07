<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/node.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Use the built-in PHP5 XML parsing functions, not this.
 *
 * @deprecated 2014
 * @package zesk
 * @subpackage tools
 */
class XML_Node extends Options {

	private $Name;

	private $Children; // array of text and CXMLNodes


	private $ChildIndex;

	function __construct($name = '', $attrs = false, $content = "") {
		parent::__construct($attrs);
		$this->Name = $name;
		if (!empty($content)) {
			$this->Children = array(
				$content
			);
		} else {
			$this->Children = array();
		}
		$this->ChildIndex = array();
	}

	function name($lower = false) {
		if ($lower)
			return strtolower($this->Name);
		return $this->Name;
	}

	function requireAttributes($mixed) {
		if (is_string($mixed)) {
			$mixed = explode(";", $mixed);
		}
		if (!is_array($mixed)) {
			return false;
		}
		foreach ($mixed as $i => $attr) {
			if ($this->has_option($attr)) {
				unset($mixed[$i]);
			}
		}
		if (count($attr)) {
			throw new Exception_Semantics($this->Name . " must contain: " . implode(",", $attr));
		}
		return true;
	}

	function children() {
		return $this->Children;
	}

	function add(&$mixed) {
		$children = & $this->Children;
		if (is_string($mixed)) {
			if (count($children) == 0) {
				$children[] = $mixed;
			} else {
				$n = count($children) - 1;
				if (is_string($children[$n])) {
					$children[$n] .= $mixed;
				} else {
					$children[] = $mixed;
				}
			}
			return true;
		} else if ($mixed instanceof XML_Node) {
			$children[] = $mixed;
			$name = $mixed->name(true);
			if (!isset($this->ChildIndex[$name])) {
				$this->ChildIndex[$name] = array();
			}
			$this->ChildIndex[$name][] = $mixed;
			return true;
		} else if ($mixed instanceof Object) {
			if (method_exists($mixed, "toXML")) {
				$xml = $mixed->toXML();
				$top = $xml->top();
				return $this->add($top);
			}
			if (method_exists($mixed, '__toString')) {
				$string = $mixed->__toString;
				return $this->add($string);
			}
		}
		throw new Exception_Convert("XML_Node::add($mixed) Can't get model as xml or string" . get_class($mixed));
	}

	/**
	 * If not found, returns an empty node with no name (to allow chaining)
	 *
	 * @param string $tag
	 * @param integer $index 0-based index of tag to retrieve
	 * @return XML_Node
	 */
	public function search($tag, $index = -1) {
		$result = $this->find($tag, $index);
		if ($result) {
			return $result;
		}
		foreach ($this->Children as $child) {
			if ($child instanceof XML_Node) {
				$result = $child->find($tag, $index);
				if ($result) {
					return $result;
				}
			}
		}
		return new XML_Node();
	}

	/**
	 * Find a named node within this node
	 *
	 * @param unknown $tag
	 * @param unknown $index
	 * @return XML_Node
	 */
	public function find($tag, $index = -1) {
		$tag = strtolower($tag);
		if (!isset($this->ChildIndex[$tag])) {
			return null;
		}

		$children = $this->ChildIndex[$tag];
		$n = count($children);
		if ($n == 0) {
			return null;
		}
		if ($index < 0) {
			return $children[$n - 1];
		} else if ($index >= $n) {
			return null;
		} else {
			return $children[$index];
		}
	}

	function find_all($tag) {
		$tag = strtolower($tag);
		if (!isset($this->ChildIndex[$tag])) {
			return array();
		}
		return $this->ChildIndex[$tag];
	}

	function content($strip = false) {
		$content = "";
		foreach ($this->Children as $child) {
			if (is_string($child)) {
				$content .= $child;
			}
		}
		if ($strip)
			return trim($content);
		return $content;
	}

	function output($indent = 0, $singles = false) {
		$prefix = str_repeat("\t", $indent);
		$single = $singles && empty($this->Children);
		$startTag = $single ? HTML::tag($this->Name, $this->options, null) : HTML::tag_open($this->Name, $this->options);
		$lines = array();
		$nChars = 0;
		foreach ($this->Children as $child) {
			if (is_string($child)) {
				$childLine = "$prefix\t" . htmlentities($child);
			} else {
				$childLine = $child->output($indent + 1, $singles);
			}
			$nChars += strlen($childLine);
			$lines[] = $childLine;
		}
		$endTag = "";
		if (!$single) {
			$endTag = HTML::tag_close($this->Name);
		}
		if (($nChars < 32) && count($lines) <= 1) {
			return $prefix . $startTag . trim(implode("", $lines)) . $endTag;
		}
		return $prefix . $startTag . "\n" . implode("\n", $lines) . "\n" . "$prefix" . $endTag;
	}
}
