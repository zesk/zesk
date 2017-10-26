<?php
namespace zesk;

abstract class Configuration_Editor extends Options {
	protected $content = null;
	public function __construct($content = "", array $options = array()) {
		parent::__construct($options);
		$this->content = $content;
	}
	
	/**
	 * Getter/setter for content
	 * 
	 * @param string $set
	 * @return \zesk\Configuration_Editor|string
	 */
	public function content($set = null) {
		if ($set !== null) {
			$this->content = $set;
			return $this;
		}
		return $this->content;
	}
	/**
	 * 
	 * @param array $edits
	 * @return string New content of configuration file
	 */
	abstract public function edit(array $edits);
}
