<?php
namespace zesk\Response;

use zesk\JSON as zeskJSON;
use zesk\Response;

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

	/**
	 *
	 * @param mixed $set
	 * @return \zesk\Response|array
	 */
	function data($set = null) {
		if ($set !== null) {
			$this->parent->content_type(Response::CONTENT_TYPE_JSON);
			$this->json = $set;
			return $this->parent;
		}
		return $this->json;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::render()
	 */
	function render($content) {
		if (is_array($content)) {
			$this->json = $content;
		} else if (is_string($content)) {
			$this->json['content'] = $content;
		}
		return $this->application->development() ? zeskJSON::encode_pretty($this->json) : json_encode($this->json);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::output()
	 */
	function output($content) {
		echo $this->render($content);
	}
}