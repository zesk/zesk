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
	function output($content) {
		echo $content;
	}
}
