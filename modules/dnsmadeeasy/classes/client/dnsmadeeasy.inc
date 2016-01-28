<?php
class Client_DNSMadeEasy extends Net_HTTP_Client {

	private $secret_key = null;

	private $api_key = null;

	private $url = null;

	private $request_id = null;

	private $request_limit = null;

	function __construct($options = null) {
		parent::__construct(null, $options);
		$this->api_key = strtolower(trim($this->option('api_key', zesk::get('dnsmadeeasy_api_key'))));
		$this->secret_key = strtolower(trim($this->option('secret_key', zesk::get('dnsmadeeasy_secret_key'))));
		
		if (!self::valid_key($this->api_key)) {
			throw new Exception_Configuration(__("API Key provided for DNS Made Easy is not a valid key: {key}", array(
				'key' => $this->api_key
			)));
		}
		if (!self::valid_key($this->secret_key)) {
			throw new Exception_Configuration(__("Secret Key provided for DNS Made Easy is not a valid key (not shown) ({0} chars)", strlen($this->secret_key)));
		}
		$this->header('x-dnsme-apiKey', $this->api_key);
		$this->header('Accept', 'application/json');
		$this->header('Content-Type', 'application/json');
		$this->want_headers(true);
	}

	public static function valid_key($key) {
		return preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $key);
	}

	function go() {
		$request_date = gmdate(DATE_RFC2822);
		$hmac = hash_hmac('sha1', $request_date, $this->secret_key);
		$this->header('x-dnsme-requestDate', $request_date);
		$this->header('x-dnsme-hmac', $hmac);
		
		$this->request_id = null;
		$this->request_limit = null;
		$this->request_remain = null;
		
		$result = parent::go();
		
		$this->request_id = $this->header('x-dnsme-requestId');
		$this->request_limit = $this->header('x-dnsme-requestLimit');
		$this->request_remain = $this->header('x-dnsmerequestsRemaining');
		
		return $result;
	}

	public static function url_default() {
		return "https://api.dnsmadeeasy.com/V2.0/";
	}

	public function api_url($path = null) {
		return path($this->option('api_url', zesk::get('dnsmadeeasy_api_url', self::url_default())), $path);
	}

	public function call($name, $method = "GET", array $arguments = array()) {
		$this->url($this->api_url($name));
		$this->method($method);
		if (count($arguments) > 0) {
			$this->data(json_encode($arguments));
		}
		return json_decode($this->go());
	}
}
