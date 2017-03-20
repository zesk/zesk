<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP/Client/Exception.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Feb 26 17:07:14 EST 2010 17:07:14
 */

namespace zesk;

/**
 * @todo subclass of zesk\Exception
 * @author kent
 *
 */
class Net_HTTP_Client_Exception extends Exception {
	/**
	 * Error code given by curl_errno
	 *
	 * @var integer
	 */
	public $errno = 0;

	/**
	 * Mapped error code to error code string
	 *
	 * @var string
	 */
	public $error_code = "";

	/**
	 * @todo Convert to arguments, if needed
	 * 
	 * @param unknown $message
	 * @param number $errno
	 * @param string $error_code
	 */
	function __construct($message, $errno = 0, $error_code = "") {
		parent::__construct($message, array(), $errno);
		$this->errno = $errno;
		$this->error_code = $error_code;
	}
}
