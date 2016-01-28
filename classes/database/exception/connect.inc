<?php
class Database_Exception_Connect extends Database_Exception {
	protected $parts = array();
	function __construct($url, $errno = 0, $message = null, array $arguments = array()) {
		if (url::valid($url)) {
			$this->parts = url::parse($url);
			$this->parts['database'] = rtrim(avalue($this->parts, '/'));
		}
		parent::__construct(null, null, $errno, $message, $arguments);
	}
	function variables() {
		return $this->parts + parent::variables();
	}
}
