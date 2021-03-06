<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Fri Sep 05 19:32:05 EDT 2008 19:32:05
 */
namespace zesk;

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

	/**
	 *
	 * @param string $name Tag name
	 * @param array $attributes tag attributes
	 * @param string $inner_html
	 * @param string $outer_html
	 * @param integer $offset
	 */
	public function __construct($name, array $attributes = array(), $inner_html = false, $outer_html = null, $offset = null) {
		parent::__construct($attributes);

		$this->name = $name;
		$this->inner_html = $inner_html;
		$this->outer_html = $outer_html;
		if ($offset !== null) {
			$this->offset = $offset;
		}
	}

	/**
	 * Is this a single tag (no close tag, ends with '\>')
	 *
	 * @return boolean
	 */
	public function is_single() {
		return !is_string($this->inner_html);
	}

	/**
	 * Getter/setter for inner HTML
	 *
	 * @param string $set
	 * @return string|self
	 */
	public function inner_html($set = null) {
		if ($set !== null) {
			$this->inner_html = $set;
			$this->outer_html = null;
			return $this;
		}
		return $this->inner_html;
	}

	/**
	 * Getter/setter for outer HTML
	 *
	 * @param string $set
	 * @return string|self
	 */
	public function outer_html($set = null) {
		if ($set !== null) {
			$this->outer_html = $set;
			return $this;
		}
		return $this->outer_html;
	}

	/**
	 * Get/set content (inner HTML)
	 *
	 * @param string $set
	 * @return string|self
	 */
	public function contents($set = null) {
		return $this->inner_html($set);
	}

	/**
	 * Convert to PHP
	 *
	 * @return string
	 */
	public function _to_php() {
		return 'new ' . __CLASS__ . '(' . implode(", ", array(
			PHP::dump($this->name),
			PHP::dump($this->option()),
			PHP::dump($this->inner_html),
			PHP::dump($this->outer_html),
			$this->offset,
		)) . ')';
	}

	/**
	 * Convert to string
	 *
	 * {@inheritDoc}
	 * @see Options::__toString()
	 */
	public function __toString() {
		return HTML::tag($this->name, $this->option(), $this->inner_html);
	}
}
