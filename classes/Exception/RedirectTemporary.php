<?php
/**
 * @package zesk
 * @subpackage exception
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class Exception_RedirectTemporary extends Exception {
	public function __construct($url, $message = null, array $arguments = array()) {
		parent::__construct($url, $message, [
			self::RESPONSE_STATUS_CODE => Net_HTTP::STATUS_TEMPORARY_REDIRECT,
		] + $arguments);
	}
}
