<?php

/**
 * 
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Database_Exception_Connect extends Database_Exception {
	
	/**
	 * 
	 * @var string
	 */
	protected $url = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $parts = array();
	
	/**
	 * 
	 * @param string $url
	 * @param string $message
	 * @param array $arguments
	 * @param integer $errno
	 */
	function __construct($url, $message = null, array $arguments = array(), $errno = null) {
		if (URL::valid($url)) {
			$this->url = $url;
			$this->parts = URL::parse($url);
			$this->parts['database'] = rtrim(avalue($this->parts, '/'));
		} else {
			$this->url = "nulldb://null/null";
		}
		$message = "Message: " . parent::getMessage() . "\nURL: " . URL::remove_password($this->url) . "\n";
		parent::__construct($message, $arguments, $errno);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see zesk\Exception::variables()
	 */
	function variables() {
		return array(
			"url" => $this->url
		) + $this->parts + parent::variables();
	}
}
