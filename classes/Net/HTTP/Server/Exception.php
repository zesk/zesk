<?php declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP/Server/Exception.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

class Net_HTTP_Server_Exception extends Exception {
	public $status = null;

	public $status_text = null;

	public function __construct($http_status, $http_message = null, $content = null) {
		$this->status = $http_status;
		if ($http_message === null) {
			$http_message = avalue(Net_HTTP::$status_text, intval($http_status), 'Unknown error');
		}
		$this->status_text = $http_message;
		parent::__construct($content, $this->status);
	}
}
