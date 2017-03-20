<?php
namespace zesk;

class Response_Text extends Response {
	/**
	 * Content-Type header
	 * @var string
	 */
	public $content_type = self::content_type_plaintext;
}
