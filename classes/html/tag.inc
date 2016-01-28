<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/html/tag.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Fri Sep 05 19:32:05 EDT 2008 19:32:05
 */

/**
 * Representation of an HTML tag.
 * contents is a string containing more HTML.
 * Inherits Options, attributes of the tag can be accessed via option, option_bool, has_option, etc.
 * @package zesk
 * @subpackage tools
 */
class HTML_Tag extends Options {
	/**
	 * Tag name
	 * @var string
	 */
	public $name;
	
	/**
	 * Contents between tags.
	 * If false, then singleton tag, e.g. <tag />
	 * @var string
	 */
	private $inner_html = null;
	
	/**
	 * Original, outer HTML including tag itself.
	 * Useful for replacing a matched tag in a document.
	 * If null, means it has not been matched in a document, or has been edited.
	 * @var string
	 */
	private $outer_html = null;
	
	/**
	 * Offset to where the tag is in the found context
	 * @var integer
	 */
	public $offset = null;
	
	function __construct($name, $attributes = false, $inner_html = false, $outer_html = null, $offset = null) {
		parent::__construct($attributes);
		
		$this->name = $name;
		$this->inner_html = $inner_html;
		$this->outer_html = $outer_html;
		if ($offset !== null) {
			$this->offset = $offset;
		}
	}
	
	function is_single() {
		return !is_string($this->inner_html);
	}
	
	function inner_html($set = null) {
		if ($set !== null) {
			$this->inner_html = $set;
			$this->outer_html = null;
		}
		return $this->inner_html;
	}
	
	function outer_html($set = null) {
		if ($set !== null) {
			$this->outer_html = $set;
		}
		return $this->outer_html;
	}
	
	function contents($set = null) {
		return $this->inner_html($set);
	}
	
	function _to_php() {
		return 'new ' . __CLASS__ . '(' . implode(", ", array(
			php::dump($this->name), 
			php::dump($this->option()), 
			php::dump($this->inner_html), 
			php::dump($this->outer_html), 
			$this->offset
		)) . ')';
	}
	
	function __toString() {
		return html::tag($this->name, $this->option(), $this->inner_html);
	}
}

