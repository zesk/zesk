<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/net/http/client.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
use zesk\file as file;
use zesk\Timestamp as Timestamp;
/**
 *
 * @package zesk
 * @subpackage system
 */
class Net_HTTP_Client extends Options {
	
	/*
	 * Sample user agent for FireFox
	 * @var string
	 */
	const user_agent_firefox = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.7; en-US; rv:1.9.0.4) Gecko/2009032609  Firefox/3.0.8";
	
	/*
	 * Sample user agent for Microsoft Internet Explorer
	 * @var string
	 */
	const user_agent_msie = "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0; .NET4.0E; .NET4.0C; BRI/2)";
	
	/**
	 * Sample user agent for Safari
	 *
	 * @var string
	 */
	const user_agent_safari = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1795.2 Safari/537.36";
	
	/**
	 * Sample user agent for Chrome
	 *
	 * @var string
	 */
	const user_agent_chrome = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1796.0 Safari/537.36";
	/**
	 * Sample user agent for Opera
	 *
	 * @var string
	 */
	const user_agent_opera = "Opera/9.80 (Windows NT 6.1; MRA 8.0 (build 5784)) Presto/2.12.388 Version/12.16";
	
	/**
	 * Sample user agents
	 *
	 * @var array
	 */
	static $sample_agents = array(
		self::user_agent_chrome,
		self::user_agent_firefox,
		self::user_agent_msie,
		self::user_agent_opera,
		self::user_agent_safari
	);
	
	/**
	 * Cookies to send
	 *
	 * @var array:string
	 */
	private $request_cookie = array();
	
	/**
	 *
	 * @var array
	 */
	private $request_headers = array();
	/**
	 *
	 * @var array
	 */
	private $skip_request_headers = array();
	
	/**
	 * lowname => value
	 *
	 * @var arrau
	 */
	private $response_headers = array();
	
	/**
	 *
	 * @var string
	 */
	private $Content;
	
	/**
	 * Redirected headers
	 *
	 * @var array
	 */
	protected $Redirects = array();
	
	/**
	 *
	 * @var string
	 */
	private $response_protocol;
	
	/**
	 *
	 * @var string
	 */
	private $response_code;
	
	/**
	 *
	 * @var string
	 */
	private $response_message;
	
	/**
	 *
	 * @var array
	 */
	private $response_cookies = array();
	
	/**
	 * Connection timeout in milliseconds
	 *
	 * @var integer
	 */
	private $connect_timeout = 5000;
	private $method = Net_HTTP::Method_GET;
	private $data = null;
	private $data_file = null;
	
	/**
	 * Curl retrieval timeout in milliseconds
	 *
	 * @var integer
	 */
	private $timeout = 5000;
	
	/**
	 * Whether to recurse when redirected
	 *
	 * @var boolean
	 */
	private $recurse = false;
	/**
	 * The user agent used for the connection
	 */
	private $user_agent = null;
	/**
	 * Path of the destination file
	 *
	 * @var string
	 */
	private $destination = null;
	/**
	 * CURL options
	 *
	 * @var array
	 */
	private $curl_opts = array();
	/**
	 * Error with connecting to server
	 */
	const Error_Connection = "Error_Connection";
	/**
	 * Error resolving host name
	 */
	const Error_Resolve_Host = "Error_Resolve_Host";
	/**
	 * Error waiting for server to respond
	 */
	const Error_Timeout = "Error_Timeout";
	/**
	 * Error connecting via SSL to remote site
	 */
	const Error_SSL_Connect = "Error_SSL_Connect";
	
	/**
	 * Create a new Net_HTTP_Client
	 *
	 * @param string $url        	
	 * @param string $options        	
	 */
	public function __construct($url = null, $options = false) {
		parent::__construct($options);
		$this->inherit_global_options();
		$this->load_from_options();
		if (!empty($url)) {
			$this->set_option("URL", $url);
		}
		if (!$this->user_agent) {
			$this->user_agent($this->default_user_agent());
		}
	}
	private function load_from_options() {
		if ($this->has_option("timeout")) {
			$this->timeout($this->option_integer("timeout"));
		}
		if ($this->has_option("user_agent")) {
			$this->user_agent($this->option("user_agent"));
		}
	}
	/**
	 * The default user agent
	 *
	 * @return string
	 */
	public function default_user_agent() {
		return $this->option("default_user_agent", 'zesk/Net_HTTP_Client ' . zesk\Version::release());
	}
	
	private function _method($method, $set = null) {
		if ($set === null) {
			return $this->method === $method;
		}
		$this->method = $method;
		return $this;
	}
	/**
	 * Get/set POST method
	 *
	 * @return boolean
	 */
	public function method_post($set = null) {
		return $this->_method(Net_HTTP::Method_POST, $set);
	}
	
	/**
	 * Get/set PUT method
	 *
	 * @return boolean
	 */
	public function method_put($set = null) {
		return $this->_method(Net_HTTP::Method_POST, $set);
	}
	
	/**
	 * Get/set POST method
	 *
	 * @return boolean
	 */
	public function method_head($set = null) {
		return $this->_method(Net_HTTP::Method_HEAD, $set);
	}
	
	/**
	 * Get/set the data associated with this client
	 *
	 * @param string $set        	
	 * @return string|Net_HTTP_Client
	 */
	public function data($set = null) {
		if ($set !== null) {
			$this->data = $set;
			return $this;
		}
		return $this->data;
	}
	
	/**
	 * Get/Set the URL associated with this HTTP client
	 *
	 * @param string $set        	
	 */
	public function url($set = null) {
		if ($set && url::is($set)) {
			$this->set_option("url", $set);
			return $this;
		}
		return $this->option("url");
	}
	
	/**
	 * Set the filename path where to store the data
	 * 
	 * @param string $set
	 *        	Optionally set the destination
	 */
	public function destination($set = null) {
		if ($set === null) {
			return $this->destination;
		}
		$this->destination = file::validate_writable($set);
		return $this;
	}
	/**
	 * Return the full error code (404,200,etc.)
	 *
	 * @return null|integer
	 */
	public function response_code() {
		return $this->response_code ? intval($this->response_code) : null;
	}
	/**
	 * Return the base error type 2,3,4,5
	 *
	 * @return null|int
	 */
	public function response_code_type() {
		if (empty($this->response_code)) {
			return null;
		}
		$code = strval($this->response_code);
		return intval(strval($code[0]));
	}
	public function response_message() {
		return $this->response_message;
	}
	/**
	 *
	 * @return $ResponseProtocol
	 */
	public function response_protocol() {
		return $this->response_protocol;
	}
	
	/**
	 * Get or set request cookies
	 *
	 * @param array $set
	 *        	Set cookies to name/value pairs for request
	 * @param boolean $append        	
	 */
	public function request_cookie(array $set = null, $append = false) {
		if ($set === null) {
			return $this->request_cookie;
		}
		$this->request_cookie = $append ? $set + $this->request_cookie : $set;
		return $this;
	}
	
	/**
	 * Format request cookies
	 * 
	 * @return string
	 */
	private function format_cookie() {
		// semicolon, comma, and white space
		$encode = array();
		foreach (str_split(";,= \r\n", 1) as $char) {
			$encode[$char] = urlencode($char);
		}
		$result = array();
		foreach ($this->request_cookie as $name => $value) {
			if ($value === true) {
				$result[] = strtr($name, $encode);
			} else {
				$result[] = strtr($name, $encode) . "=" . strtr($value, $encode);
			}
		}
		return implode("; ", $result);
	}
	
	/**
	 * Retrieve a request header
	 *
	 * @param string $name        	
	 * @param string $set        	
	 * @return mixed|Net_HTTP_Client
	 */
	public function request_header($name = null, $set = null) {
		if ($name === null) {
			return arr::map_keys($this->request_headers, Net_HTTP::$request_headers);
		}
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->request_header($k, $v);
			}
			return $this;
		}
		$lowname = strtolower($name);
		if ($set === null) {
			return avalue($this->request_headers, $lowname, null);
		}
		$this->request_headers[$lowname] = $set;
		return $this;
	}
	
	/**
	 * When an HTTP header is handled by a curl option, add it here so it's not sent twice.
	 *
	 * Not sure how smart curl is, but probably better not to be redundant.
	 *
	 * @param string $name        	
	 */
	private function skip_request_header($name) {
		$name = strtolower($name);
		$this->skip_request_headers[$name] = $name;
	}
	
	/**
	 * Retrieve the response content type
	 *
	 * @return string
	 */
	public function content_type() {
		$header = avalue($this->response_headers, 'content-type');
		$header = trim(str::left($header, ';', $header));
		return $header;
	}
	
	/**
	 * Get/set the request timeout in miiliseconds
	 * 
	 * @param integer $milliseconds        	
	 * @return integer|Net_HTTP_Client
	 */
	public function timeout($milliseconds = null) {
		if ($milliseconds !== null) {
			$this->timeout = intval($milliseconds);
			return $this;
		}
		return $this->timeout;
	}
	/**
	 * Get/set the request timeout in miiliseconds
	 *
	 * @param integer $milliseconds        	
	 * @return integer|Net_HTTP_Client
	 */
	public function connect_timeout($milliseconds = null) {
		if ($milliseconds !== null) {
			$this->connect_timeout = intval($milliseconds);
			return $this;
		}
		return $this->connect_timeout;
	}
	
	/**
	 * Get/set the method
	 *
	 * @param string $set
	 *        	Value to set the method to
	 * @return string|Net_HTTP_Client
	 */
	public function method($set = null) {
		if ($set !== null) {
			$this->method = avalue(Net_HTTP::$methods, $set, $this->method);
			return $this;
		}
		return $this->method;
	}
	
	/**
	 * Initialize our curl options before executing the curl object
	 */
	private function _method_open() {
		$httpHeaders = array();
		$this->curl_opts = array(
			CURLOPT_ENCODING => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => $this->option_bool("VerifySSL", false)
		);
		$data = $this->_encode_data();
		switch ($this->method) {
			case Net_HTTP::Method_GET:
				break;
			case Net_HTTP::Method_POST:
				$this->curl_opts[CURLOPT_POST] = 1;
				$this->curl_opts[CURLOPT_POSTFIELDS] = $data;
				$httpHeaders[] = 'Content-Length: ' . strlen($data);
				$this->skip_request_header('content-length');
				break;
			case Net_HTTP::Method_HEAD:
				$this->curl_opts[CURLOPT_NOBODY] = 1;
				break;
			case Net_HTTP::Method_PUT:
				$this->data_file = tmpfile();
				fwrite($this->data_file, $data);
				fseek($this->data_file, 0);
				$this->curl_opts[CURLOPT_PUT] = true;
				$this->curl_opts[CURLOPT_INFILE] = $this->data_file;
				$this->curl_opts[CURLOPT_INFILESIZE] = strlen($data);
				$httpHeaders[] = 'Content-Length: ' . strlen($data);
				$this->skip_request_header('content-length');
				break;
			default :
				$this->curl_opts[CURLOPT_CUSTOMREQUEST] = $this->method;
				break;
		}
		return $httpHeaders;
	}
	
	/**
	 * Close our curl options after curl has completed
	 */
	private function _method_close() {
		if ($this->data_file) {
			fclose($this->data_file);
		}
		if ($this->destination) {
			fclose($this->curl_opts[CURLOPT_FILE]);
			fclose($this->curl_opts[CURLOPT_WRITEHEADER]);
		}
	}
	
	/**
	 * Set curl options related to timeouts and network activity
	 */
	private function _curl_opts_timeouts() {
		if ($this->connect_timeout > 0) {
			if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
				$this->curl_opts[CURLOPT_CONNECTTIMEOUT_MS] = $this->connect_timeout;
			} else {
				$this->curl_opts[CURLOPT_CONNECTTIMEOUT] = intval($this->connect_timeout / 1000);
			}
		}
		// CURLOPT_TIMEOUT_MS since PHP 5.2.3
		if ($this->timeout > 0) {
			if (defined('CURLOPT_TIMEOUT_MS')) {
				$this->curl_opts[CURLOPT_TIMEOUT_MS] = $this->timeout;
			} else {
				$this->curl_opts[CURLOPT_TIMEOUT] = intval($this->timeout / 1000);
			}
		}
	}
	
	/**
	 * Set curl options related to If-Modified-Since
	 */
	private function _curl_opts_if_modified() {
		if (($value = $this->request_header("If-Modified-Since")) !== null) {
			$this->curl_opts[CURLOPT_TIMECONDITION] = CURL_TIMECOND_IFMODSINCE;
			$this->curl_opts[CURLOPT_TIMEVALUE] = $value;
			$this->skip_request_header("If-Modified-Since");
		}
	}
	
	/**
	 * Set curl options related to User-Agent
	 */
	private function _curl_opts_useragent() {
		$this->curl_opts[CURLOPT_USERAGENT] = $this->request_header(Net_HTTP::request_User_Agent);
		$this->skip_request_header(Net_HTTP::request_User_Agent);
	}
	
	/**
	 * Set curl options related to the method
	 */
	private function _curl_opts_method() {
		$returnHeaders = $this->want_headers();
		$is_head = $this->method_head();
		if ($is_head) {
			$returnHeaders = $this->want_headers(true);
			$this->curl_opts[CURLOPT_NOBODY] = 1;
		}
		if ($returnHeaders) {
			$this->curl_opts[CURLOPT_HEADER] = 1;
		} else {
			$this->curl_opts[CURLOPT_HEADER] = 0;
		}
	}
	private function _curl_opts_follow() {
		if ($this->recurse) {
			$this->curl_opts[CURLOPT_FOLLOWLOCATION] = true;
		}
		if ($this->option_bool("follow_location")) {
			$this->curl_opts[CURLOPT_FOLLOWLOCATION] = true;
			$this->curl_opts[CURLOPT_MAXREDIRS] = $this->option_integer("follow_location_maximum", 7);
		}
	}
	private function _curl_opts_host() {
		$parts = parse_url($this->url());
		$host = avalue($parts, 'host');
		$scheme = avalue($parts, 'scheme');
		$default_port = url::protocol_default_port($scheme);
		$port = intval(avalue($parts, 'port', $default_port));
		if ($port !== $default_port) {
			$host .= ":$port";
		}
		$this->request_header("Host", $host);
	}
	private function _curl_opts_cookie() {
		if (count($this->request_cookie)) {
			$this->request_header("Cookie", self::format_cookie());
		}
	}
	private function _curl_opts_range() {
		if (($range = $this->request_header("Range")) !== null) {
			$this->curl_opts[CURLOPT_RANGE] = substr($range, 6);
			$this->skip_request_header('Range');
		}
	}
	private function _curl_opts_headers() {
		foreach ($this->request_headers as $k => $values) {
			if (!array_key_exists(strtolower($k), $this->skip_request_headers)) {
				if (is_string($values)) {
					$values = array(
						$values
					);
				}
				$k = avalue(Net_HTTP::$request_headers, $k, $k);
				foreach ($values as $value) {
					$httpHeaders[] = "$k: $value";
				}
			}
		}
		$this->curl_opts[CURLOPT_HTTPHEADER] = $httpHeaders;
	}
	private function _curl_opts_destination() {
		if ($this->destination) {
			$dest_fp = fopen($this->destination, "w");
			if (!$dest_fp) {
				throw new Exception_File_Permission($dest_fp, "Not writable");
			}
			$this->curl_opts[CURLOPT_FILE] = $dest_fp;
			$dest_headers_name = $this->destination . "-headers";
			$dest_headers_fp = fopen($dest_headers_name, "w");
			$this->curl_opts[CURLOPT_WRITEHEADER] = $dest_headers_fp;
			
			return $dest_headers_name;
		}
		return null;
	}
	private function _curl_opts_close_destination() {
	}
	private function _parse_headers($dest_headers_name = null) {
		if ($dest_headers_name) {
			$all_headers = file_get_contents($dest_headers_name);
			$headers_list = explode("\r\n\r\n", $all_headers);
			$this->response_headers = null;
			foreach ($headers_list as $headers) {
				if (empty($headers)) {
					break;
				}
				if (is_array($this->response_headers)) {
					$this->Redirects[] = $this->response_headers;
				}
				$this->parseHeaders($headers);
			}
			unlink($dest_headers_name);
			file::trim($this->destination, strlen($all_headers));
		} else if ($this->want_headers()) {
			$this->parseHeaders();
		}
	}
	public function go() {
		if (!function_exists("curl_init")) {
			throw new Exception("Net_HTTP_Client::go(): CURL not integrated!");
		}
		$url = $this->option("URL");
		if (empty($url)) {
			throw new Exception("Net_HTTP_Client::go called with no URL specified");
		}
		
		$httpHeaders = $this->_method_open();
		$this->_curl_opts_method();
		$this->_curl_opts_timeouts();
		$this->_curl_opts_if_modified();
		$this->_curl_opts_useragent();
		$this->_curl_opts_follow();
		$this->_curl_opts_host();
		$this->_curl_opts_cookie();
		$this->_curl_opts_headers();
		$dest_headers_name = $this->_curl_opts_destination();
		
		if ($this->option("debug")) {
			dump($url);
			dump($httpHeaders);
		}
		// Supress "Operation timed out after 5003 milliseconds with 0 bytes received"
		$curl = curl_init($url);
		foreach ($this->curl_opts as $option => $value) {
			curl_setopt($curl, $option, $value);
		}
		$this->Content = @curl_exec($curl);
		$this->_method_close($curl);
		$errno = curl_errno($curl);
		$error = curl_error($curl);
		
		$this->_parse_headers($dest_headers_name);
		
		if ($this->option_bool("debug") && $this->destination) {
			if (file_exists($this->destination)) {
				$command = Command::running();
				if ($command) {
					$command->readline(__CLASS__ . " : CHECK " . $this->url() . " Destination $this->destination");
				}
			}
		}
		
		curl_close($curl);
		if ($errno !== 0) {
			if ($errno === CURLE_COULDNT_RESOLVE_HOST) {
				$host = url::parse($this->url(), "host");
				throw new Exception_DomainLookup($host, "Retrieving URL {url}", array(
					"url" => $this->url()
				), $errno);
			}
			$errno_map = array(
				CURLE_COULDNT_CONNECT => self::Error_Connection,
				CURLE_COULDNT_RESOLVE_HOST => self::Error_Resolve_Host,
				CURLE_OPERATION_TIMEOUTED => self::Error_Timeout,
				CURLE_SSL_CONNECT_ERROR => self::Error_SSL_Connect
			);
			throw new Net_HTTP_Client_Exception($error, $errno, avalue($errno_map, $errno, "UnknownErrno-$errno"));
		}
		return $this->Content;
	}
	public static function simpleGet($url) {
		$parts = parse_url($url);
		$protocol = avalue($parts, "scheme");
		if ($protocol !== "http") {
			return false;
		}
		$f = fopen($url, "rb");
		if (!$f) {
			return false;
		}
		$contents = "";
		while (!feof($f)) {
			$contents .= fread($f, 4096);
		}
		return $contents;
	}
	public function domain() {
		$url = $this->option("URL");
		return url::host($url);
	}
	static public function url_content_length($url) {
		$headers = self::url_headers($url);
		return to_integer(aevalue($headers, "Content-Length"));
	}
	static public function url_headers($url) {
		$x = new Net_HTTP_Client($url);
		$x->method_head(true);
		$x->go();
		$result = $x->responseCodeType();
		if ($result !== 2) {
			throw new Net_HTTP_Client_Exception("Net_HTTP_Client::urlHeaders($url) returned response code $result ", $x->responseCode());
		}
		return $x->response_header();
	}
	private function _encode_data() {
		$data = $this->data;
		if (is_string($data)) {
			return $data;
		}
		if (!is_array($data)) {
			return null;
		}
		return http_build_query($data);
	}
	public function response_cookies($set = null) {
		if ($set instanceof Net_HTTP_Client_Cookie) {
			$this->response_cookies[] = $set;
		} else if (is_array($set)) {
			foreach ($set as $item) {
				$this->cookies($item);
			}
		}
		return $this->response_cookies;
	}
	/*
	 * Cookie Handling
	 * @todo move this out of here, use a Cookie Jar or something
	 */
	private function cookieString($url) {
		$parts = parse_url($url);
		$host = strtolower(avalue($parts, "host", ""));
		$path = avalue($parts, "path", "/");
		$secure = strtolower(avalue($parts, "scheme", "")) === "https" ? true : false;
		$results = array();
		foreach ($this->response_cookies as $cookies) {
			if (!is_array($cookies)) {
				$cookies = array(
					$cookies
				);
			}
			foreach ($cookies as $cookie) {
				/* @var $cookie Net_HTTP_Client_Cookie */
				if ($cookie->matches($host, $path)) {
					if (!$secure && $cookie->isSecure()) {
						continue;
					}
					$results[] = $cookie->string();
				}
			}
		}
		if (empty($results)) {
			return false;
		}
		return implode("; ", $results);
	}
	private function deleteCookie($cookieName, $domain, $path) {
		if (!isset($this->response_cookies[$cookieName])) {
			return false;
		}
		$cookies = $this->response_cookies[$cookieName];
		if (is_array($cookies)) {
			foreach ($cookies as $k => $cookie) {
				assert($cookie instanceof Net_HTTP_Client_Cookie);
				if ($cookie->matches($domain, $path)) {
					unset($this->response_cookies[$cookieName][$k]);
					return true;
				}
			}
		} else {
			$cookie = $cookies;
			assert($cookie instanceof Net_HTTP_Client_Cookie);
			if ($cookie->matches($domain, $path)) {
				unset($this->response_cookies[$cookieName]);
				return true;
			}
		}
		return false;
	}
	private function findCookie($cookieName, $domain, $path) {
		if (!isset($this->response_cookies[$cookieName]))
			return false;
		$cookies = $this->response_cookies[$cookieName];
		if (is_array($cookies)) {
			foreach ($cookies as $cookie) {
				assert($cookie instanceof Net_HTTP_Client_Cookie);
				if ($cookie->matches($domain, $path)) {
					return $cookie;
				}
			}
		} else {
			$cookie = $cookies;
			assert($cookie instanceof Net_HTTP_Client_Cookie);
			if ($cookie->matches($domain, $path)) {
				return $cookie;
			}
		}
		return false;
	}
	private function addCookie($cookieName, $cookieValue, $domain = false, $path = false, $expires = false, $secure = false) {
		if (!$domain) {
			$domain = $this->domain();
		}
		if (!$path) {
			$path = "/";
		}
		arr::append($this->response_cookies, $cookieName, new Net_HTTP_Client_Cookie($cookieName, $cookieValue, $domain, $path, $expires, $secure));
	}
	private function parseCookies() {
		if (!isset($this->response_headers["set-cookie"])) {
			return false;
		}
		$cookies = $this->response_headers["set-cookie"];
		if (!is_array($cookies))
			$cookies = array(
				$cookies
			);
		foreach ($cookies as $cookie) {
			$parts = explode(";", $cookie);
			$cookie_item = array_shift($parts);
			list($cookieName, $cookieValue) = str::pair($cookie_item, "=", $cookie_item, null);
			$cookieName = trim($cookieName);
			if (empty($cookieName)) {
				continue;
			}
			$path = "/";
			$secure = false;
			$domain = $this->domain();
			$expireString = false;
			foreach ($parts as $cname) {
				list($cname, $cvalue) = str::pair($cname, "=", $cname);
				$cname = strtolower(trim($cname));
				$cvalue = trim($cvalue);
				switch ($cname) {
					case "path":
						$path = $cvalue;
						break;
					case "secure":
						$secure = true;
						break;
					case "domain":
						$domain = $cvalue;
						break;
					case "expires":
						$expireString = $cvalue;
						break;
				}
			}
			$expires = false;
			$deleteCookie = false;
			if ($expireString) {
				$expires = new Timestamp($expireString);
				$now = Timestamp::now();
				if ($expires->before($now)) {
					$deleteCookie = true;
				}
			}
			if ($deleteCookie) {
				$this->deleteCookie($cookieName, $domain, $path);
			} else {
				$cookieObject = $this->findCookie($cookieName, $domain, $path);
				if ($cookieObject) {
					$cookieObject->update($cookieValue, $expires);
				} else {
					$this->addCookie($cookieName, $cookieValue, $domain, $path, $expires, $secure);
				}
			}
		}
		return true;
	}
	
	/**
	 * Getter/setter for recurse flag
	 *
	 * @param string $set        	
	 * @return boolean|Net_HTTP_Client
	 */
	public function recurse($set = null) {
		if ($set !== null) {
			$this->recurse = $set;
			return $this;
		}
		return $this->recurse;
	}
	public function content() {
		return $this->Content;
	}
	private function parseHeaders($content = null) {
		$headers = ($content === null) ? $this->Content : $content;
		$this->response_headers = array();
		list($headers, $new_content) = pair($headers, "\r\n\r\n", $headers, "");
		$headers = explode("\r\n", $headers);
		$response = explode(" ", array_shift($headers), 3);
		$this->response_protocol = array_shift($response);
		$this->response_code = intval(array_shift($response));
		$this->response_message = array_shift($response);
		foreach ($headers as $h) {
			if (empty($h)) {
				continue;
			}
			list($h, $v) = pair($h, ":");
			$h = trim($h);
			$lowh = strtolower($h);
			arr::append($this->response_headers, $lowh, ltrim($v));
		}
		$this->parseCookies();
		if ($content === null) {
			$this->Content = $new_content;
		}
		return count($this->response_headers);
	}
	
	/**
	 * Getter only
	 *
	 * @param string $name        	
	 * @param string $default        	
	 */
	public function response_header($name = null, $default = null) {
		if ($name === null) {
			return arr::map_keys($this->response_headers, Net_HTTP::$response_headers);
		}
		return avalue($this->response_headers, strtolower($name), $default);
	}
	/**
	 * Getter/setter for follow location
	 *
	 * @param string $set        	
	 * @return Net_HTTP_Client|boolean
	 */
	public function follow_location($set = null) {
		if (is_bool($set)) {
			$this->set_option("follow_location", $set);
			return $this;
		}
		return $this->option_bool('follow_location');
	}
	/**
	 * Getter/setter for User-Agent for request
	 *
	 * @param string $set        	
	 * @return Net_HTTP_Client|string
	 */
	public function user_agent($set = null) {
		if ($set !== null) {
			$this->request_header(Net_HTTP::request_User_Agent, $set);
			return $this;
		}
		return $this->request_header(Net_HTTP::request_User_Agent);
	}
	
	/**
	 * Retrieve the filename of the file per the Content-Disposition header
	 *
	 * @return string
	 */
	public function filename() {
		// Content-Disposition: attachment; filename=foo.tar.gz
		$dispositions = arr::trim_clean(explode(";", $this->response_header(Net_HTTP::response_Content_Disposition)));
		while (($disposition = array_shift($dispositions)) !== null) {
			list($name, $value) = pair($disposition, "=", null, null);
			if ($name === "filename") {
				return unquote($value);
			}
		}
		return basename(url::path($this->url()));
	}
	/**
	 * Get/Set that we want to retrieve the headers from the remote server
	 *
	 * @param string $set        	
	 */
	public function want_headers($set = null) {
		if (is_bool($set)) {
			$this->set_option('ReturnHeaders', $set);
			return $this;
		}
		return $this->option_bool('ReturnHeaders', true);
	}
	/**
	 *
	 * @deprecated 2015
	 * @return $ResponseMessage
	 */
	public function responseMessage() {
		zesk::deprecated();
		return $this->response_message;
	}
	/**
	 *
	 * @deprecated 2015
	 * @return string
	 */
	public function responseProtocol() {
		zesk::deprecated();
		return $this->response_protocol;
	}
	/**
	 *
	 * @deprecated 2015-09
	 * @param unknown $name        	
	 * @param string $default        	
	 * @return string|Ambigous <mixed, array>
	 */
	public function getHeader($name, $default = false) {
		zesk::deprecated();
		if (!is_array($this->request_headers)) {
			return $default;
		}
		return avalue($this->request_headers, $name, $default);
	}
	/**
	 *
	 * @deprecated 2015-09
	 * @param string $name        	
	 * @param string $set        	
	 */
	public function hasHeaders() {
		zesk::deprecated();
		return is_array($this->request_headers);
	}
	/**
	 *
	 * @deprecated 2015-09
	 * @param string $name        	
	 * @param string $set        	
	 */
	public function header($name = null, $set = null) {
		zesk::deprecated();
		return $this->request_header($name, $set);
	}
	/**
	 *
	 * @deprecated 2015-09
	 */
	public function headers() {
		zesk::deprecated();
		return $this->request_headers;
	}
	
	/**
	 *
	 * @deprecated use url_content_length
	 * @param unknown $url        	
	 * @return mixed
	 */
	static public function urlContentLength($url) {
		zesk::deprecated();
		return self::url_content_length($url);
	}
	
	/**
	 *
	 * @deprecated 2015-09
	 * @return boolean
	 */
	public function isPost() {
		zesk::deprecated();
		return $this->is_post();
	}
	
	/**
	 *
	 * @deprecated 2015-09
	 */
	public function setHEAD() {
		zesk::deprecated();
		$this->method = Net_HTTP::Method_HEAD;
	}
	/**
	 *
	 * @deprecated 2015-09
	 */
	public function isHEAD() {
		zesk::deprecated();
		return $this->method === Net_HTTP::Method_HEAD;
	}
}


