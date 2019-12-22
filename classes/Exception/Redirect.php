<?php
/**
 * @package zesk
 * @subpackage exception
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
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
	 * Pass as an argument to set the `zesk\Response::status_code()`
	 *
	 * @var string
	 */
	const RESPONSE_STATUS_CODE = "status_code";

	/**
	 * Pass as an argument to set the `zesk\Response::status_message()`
	 * @var string
	 */
	const RESPONSE_STATUS_MESSAGE = "status_message";

	/**
	 * Create a redirect
	 *
	 * @param string $url
	 * @param string $message
	 */
	public function __construct($url, $message = null, array $arguments = array()) {
		$this->url = $url;
		parent::__construct($message, $arguments);
	}

	/**
	 *
	 * @param unknown $set
	 * @return \zesk\Exception_Redirect|string
	 */
	public function url($set = null) {
		if ($set !== null) {
			$this->url = $set;
			return $this;
		}
		return $this->url;
	}

	/**
	 *
	 * @return string|NULL
	 */
	public function status_message() {
		return avalue($this->arguments, self::RESPONSE_STATUS_MESSAGE);
	}

	/**
	 *
	 * @return integer|NULL
	 */
	public function status_code() {
		return avalue($this->arguments, self::RESPONSE_STATUS_CODE);
	}
}
