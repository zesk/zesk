<?php
namespace zesk\Response;

use zesk\Response;

/**
 * The simplest of types
 *
 * @author kent
 *
 */
class Text extends Type {
	public function output($content) {
		echo $content;
	}
	public function to_json() {
		return array(
			"content" => $content
		);
	}
}
