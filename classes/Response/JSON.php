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

	/*
	 if (count($this->response_data) === 0) {
	 $content = $set;
	 } else {
	 if (is_array($set)) {
	 $content = $set + $this->response_data;
	 } else {
	 $content = array(
	 'content' => $set
	 ) + $this->response_data;
	 }
	 }
	 $this->content = is_array($this->content) ? $content + $this->content : $content;
	 return $this;

	 */
	function data($set = null) {
		if ($set !== null) {
			$this->parent->content_type(Response::CONTENT_TYPE_JSON);
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