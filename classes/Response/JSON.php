<?php
namespace zesk\Response;

use zesk\JSON as zeskJSON;

class JSON extends Type {
	/**
	 *
	 * @var array
	 */
	private $json = array();

	/**
	 *
	 * @param \zesk\Response $response
	 */
	function initialize() {
		$this->json = array();
	}
	function json(array $set = null) {
		if ($set !== null) {
			$this->json = $set;
			return $this->parent;
		}
		return $this->json;
	}
	function render($content) {
		if (is_array($content)) {
			$this->json = $content;
		} else if (is_string($content)) {
			$this->json['content'] = $content;
		}
		return $this->application->development() ? zeskJSON::encode_pretty($this->json) : json_encode($this->json);
	}
}