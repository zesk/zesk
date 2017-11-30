<?php
namespace DNSMadeEasy;

use zesk\Exception_Configuration;
use zesk\Net_HTTP_Client;
use zesk\Application;

class Client extends \zesk\Net_HTTP_Client {
	/**
	 * 
	 * @var string
	 */
	private $secret_key = null;
	/**
	 * 
	 * @var string
	 */
	private $api_key = null;
	/**
	 * 
	 * @var string
	 */
	private $url = null;
	/**
	 * 
	 * @var string
	 */
	private $request_id = null;
	/**
	 * 
	 * @var numeric
	 */
	private $request_limit = null;
	
	/**
	 * 
	 * @param unknown $options
	 * @throws Exception_Configuration
	 */
	function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->api_key = strtolower(trim($this->option('api_key')));
		$this->secret_key = strtolower(trim($this->option('secret_key')));
		if (!self::valid_key($this->api_key)) {
			throw new Exception_Configuration(__("API Key provided for DNS Made Easy is not a valid key: {key}", array(
				'key' => $this->api_key
			)));
		}
		if (!self::valid_key($this->secret_key)) {
			throw new Exception_Configuration(__("Secret Key provided for DNS Made Easy is not a valid key (not shown) ({0} chars)", strlen($this->secret_key)));
		}
		$this->request_header('x-dnsme-apiKey', $this->api_key);
		$this->request_header('Accept', 'application/json');
		$this->request_header('Content-Type', 'application/json');
		$this->want_headers(true);
	}
	
	/**
	 * 
	 * @param unknown $key
	 * @return number
	 */
	public static function valid_key($key) {
		return preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $key);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Net_HTTP_Client::go()
	 */
	function go() {
		$request_date = gmdate(DATE_RFC2822);
		$hmac = hash_hmac('sha1', $request_date, $this->secret_key);
		$this->request_header('x-dnsme-requestDate', $request_date);
		$this->request_header('x-dnsme-hmac', $hmac);
		
		$this->request_id = null;
		$this->request_limit = null;
		$this->request_remain = null;
		
		$result = parent::go();
		
		$this->request_id = $this->response_header('x-dnsme-requestId');
		$this->request_limit = $this->response_header('x-dnsme-requestLimit');
		$this->request_remain = $this->response_header('x-dnsmerequestsRemaining');
		
		return $result;
	}
	
	/**
	 * 
	 * @return string
	 */
	public static function url_default() {
		return "https://api.dnsmadeeasy.com/V2.0/";
	}
	
	/**
	 * 
	 * @param unknown $path
	 * @return string
	 */
	public function api_url($path = null) {
		return path($this->option('api_url', self::url_default()), $path);
	}
	
	/**
	 * 
	 * @param unknown $name
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function call($name, $method = "GET", array $arguments = array()) {
		$this->url($this->api_url($name));
		$this->method($method);
		if (count($arguments) > 0) {
			$this->data(json_encode($arguments));
		}
		return json_decode($this->go());
	}
}
