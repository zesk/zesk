<?php
namespace zesk;

class PHP_Token {
	public $type;

	public $contents;

	public function __construct($rawToken) {
		if (is_array($rawToken)) {
			$this->type = $rawToken[0];
			$this->contents = $rawToken[1];
		} else {
			$this->type = -1;
			$this->contents = $rawToken;
		}
	}

	public function is_ternary() {
		return $this->contents === '?';
	}
}
