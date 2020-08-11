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
class Database_Exception_Connect extends Exception {
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
	public function __construct($url, $message = null, array $arguments = array(), $errno = null) {
		if (URL::valid($url)) {
			$this->url = $url;
			$arguments['safe_url'] = URL::remove_password($url);
			$arguments += Database::url_parse($url);
			$arguments['database'] = $arguments['name'];
		} else {
			$this->url = "nulldb://null/null";
		}
		if (strpos($message, "{safe_url}") === false) {
			$message .= " ({safe_url})";
		}
		parent::__construct($message, $arguments, $errno);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see zesk\Exception::variables()
	 */
	public function variables() {
		return array(
			"url" => $this->url,
		) + $this->parts + parent::variables();
	}
}
