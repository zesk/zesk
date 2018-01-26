<?php
namespace zesk\Response;

use zesk\Response;

class Text extends Response {
	function initialize() {
	}
	function hasFile() {
		return false;
	}
	function headers() {
	}
	function render($content) {
		return $content;
	}
	function passthru($content) {
		echo $this->render($content);
	}
}
