<?php
declare(strict_types=1);
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
 * Inherits Options, attributes of the tag can be accessed via option, optionBool, has_option, etc.
 * @package zesk
 * @subpackage tools
 */
class HTML_Tag extends Options {
	/**
	 * Tag name
	 * @var string
	 */
	public string $name = '';

	/**
	 * Contents between tags.
	 * If false, then singleton tag, e.g. <tag />
	 * @var string
	 */
	private string $inner_html = '';

	/**
	 * Original, outer HTML including tag itself.
	 * Useful for replacing a matched tag in a document.
	 * If null, means it has not been matched in a document, or has been edited.
	 * @var string
	 */
	private string $outer_html = '';

	/**
	 * Offset to where the tag is in the found context
	 * @var integer
	 */
	public int $offset = -1;

	/**
	 *
	 * @param string $name Tag name
	 * @param array $attributes tag attributes
	 * @param string $inner_html
	 * @param string $outer_html
	 * @param integer $offset
	 */
	public function __construct(
		string $name,
		array $attributes = [],
		string $inner_html = '',
		string $outer_html = '',
		int	$offset = -1
	) {
		parent::__construct($attributes);

		$this->name = $name;
		$this->inner_html = $inner_html;
		$this->outer_html = $outer_html;
		$this->offset = $offset;
	}

	/**
	 * Is this a single tag (no close tag, ends with '\>')
	 *
	 * @return boolean
	 */
	public function isSingle(): bool {
		return $this->inner_html === '';
	}

	/**
	 * Getterfor inner HTML
	 *
	 * @return string
	 */
	public function innerHTML(): string {
		return $this->inner_html;
	}

	/**
	 * Getter/setter for inner HTML
	 *
	 * @param string $set
	 * @return string|self
	 */
	public function setInnerHTML(string $set): self {
		$this->inner_html = $set;
		$this->outer_html = '';
		return $this;
	}

	/**
	 * Getter/setter for outer HTML
	 *
	 * @param string $set
	 * @return string
	 */
	public function outerHTML(): string {
		return $this->outer_html;
	}

	/**
	 * Getter/setter for outer HTML
	 *
	 * @param string $set
	 * @return self
	 */
	public function setOuterHTML(string $set): self {
		$this->outer_html = $set;
		return $this;
	}

	/**
	 * Get content (inner HTML)
	 *
	 * @return string
	 */
	public function contents(): string {
		return $this->innerHTML();
	}

	/**
	 * Get/set content (inner HTML)
	 *
	 * @param string $set
	 * @return self
	 */
	public function setContents(string $set): self {
		return $this->setInnerHTML($set);
	}

	/**
	 * Convert to PHP
	 *
	 * @return string
	 */
	public function _to_php() {
		return 'new ' . __CLASS__ . '(' . implode(', ', [
				PHP::dump($this->name),
				PHP::dump($this->options()),
				PHP::dump($this->inner_html),
				PHP::dump($this->outer_html),
				$this->offset,
			]) . ')';
	}

	/**
	 * Convert to string
	 *
	 * {@inheritDoc}
	 * @see Options::__toString()
	 */
	public function __toString() {
		return HTML::tag($this->name, $this->options(), $this->inner_html);
	}

	/**
	 * Getter/setter for outer HTML
	 *
	 * @param string $set
	 * @return string
	 * @deprecated 2022-01
	 */
	public function outer_html($set = null) {
		if ($set !== null) {
			$this->setOuterHTML(strval($set));
		}
		return $this->outerHTML();
	}

	/**
	 * Getter/setter for inner HTML
	 *
	 * @param string $set
	 * @return string
	 * @deprecated 2022-01
	 */
	public function inner_html($set = null) {
		if ($set !== null) {
			$this->setInnerHTML(strval($set));
		}
		return $this->innerHTML();
	}

	/**
	 * Is this a single tag (no close tag, ends with '\>')
	 *
	 * @return boolean
	 * @deprecated 2022-01
	 * @see isSingle
	 */
	public function is_single(): bool {
		return $this->isSingle();
	}
}
