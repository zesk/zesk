<?php
/**
 * @package zesk
 * @subpackage exception
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see theme/zesk/exception/redirect
 * @author kent
 */
class Exception_Redirect extends Exception {
	/**
	 *
	 * @var string
	 */
	public $url = null;

	/**
	 * Create a redirect
	 *
	 * @param string $url
	 * @param string $message
	 */
	function __construct($url, $message = null, array $arguments = array()) {
		$this->url = $url;
		parent::__construct($message, $arguments);
	}

	/**
	 *
	 * @param unknown $set
	 * @return \zesk\Exception_Redirect|string
	 */
	function url($set = null) {
		if ($set !== null) {
			$this->url = $set;
			return $this;
		}
		return $this->url;
	}
}