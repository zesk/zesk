<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage exception
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class Exception_RedirectTemporary extends Exception_Redirect {
	public function __construct($url, $message = null, array $arguments = []) {
		parent::__construct($url, $message, [
			self::RESPONSE_STATUS_CODE => Net_HTTP::STATUS_TEMPORARY_REDIRECT,
		] + $arguments);
	}
}
