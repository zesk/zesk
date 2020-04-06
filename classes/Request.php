<?php

/**
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

/**
 * Abstraction for web requests
 *
 * @package zesk
 * @subpackage system
 * @see Net_HTTP_Server_Request
 */
class Request extends Hookable {
	/**
	 * Default name of file to read for POST ar PUT content
	 *
	 * @var string
	 */
	const default_data_file = "php://input";

	/**
	 * Method of request GET, POST, PUT, POST, DELETE etc.
	 *
	 * @var string
	 */
	protected $method = "GET";

	/**
	 * Requested URI
	 *
	 * @var string
	 */
	protected $uri = "/";

	/**
	 * Request headers (reconstructed)
	 */
	protected $headers = array();

	/**
	 * Request headers parse cache
	 */
	private $headers_parsed = array();

	/**
	 * Cookies
	 */
	protected $cookies = array();

	/**
	 * RAW data as POSTed or PUTted
	 *
	 * When this value is "true" then need to load from $this->data_file
	 *
	 * @var mixed
	 */
	protected $data_raw = "";

	/**
	 * Processed data converted to internal structure
	 *
	 * @var mixed
	 */
	protected $data = "";

	/**
	 * Where to retrieve the data from
	 *
	 * @var string
	 */
	protected $data_file = self::default_data_file;

	/**
	 * Inherit data from another object
	 *
	 * @var Request
	 */
	protected $data_inherit = null;

	/**
	 * Parsed request variables (see $_REQUEST)
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Parsed file uploads (see $_FILES)
	 *
	 * @var array
	 */
	protected $files = array();

	/**
	 * Complete URL
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 *
	 * @var array
	 */
	protected $url_parts = array(
		'host' => null,
		'scheme' => null,
		'path' => null,
	);

	/**
	 *
	 * @var Net_HTTP_UserAgent
	 */
	protected $user_agent = null;

	/**
	 * Way to mock IP addresses if needed.
	 * Defaults to $_SERVER variables based on load balancers or reverse proxies.
	 *
	 * @var string
	 */
	protected $ip = null;

	/**
	 * Server IP address
	 *
	 * Defaults to $_SERVER['SERVER_ADDR']
	 *
	 * @var string
	 */
	protected $server_ip = null;

	/**
	 * Remote IP address
	 *
	 * Defaults to $_SERVER['REMOTE_ADDR']
	 *
	 * @var string
	 */
	protected $remote_ip = null;

	/**
	 *
	 * @var string
	 */
	protected $init = null;

	/**
	 *
	 * @param Application $application
	 * @param array|Request|null $settings If NULL, uses PHP globals to initialize
	 * @return self
	 */
	public static function factory(Application $application, $settings = null) {
		return $application->factory(__CLASS__, $application, $settings);
	}

	/**
	 * Create a new Request object
	 *
	 * @param array|Request|null $settings If NULL, uses PHP globals to initialize
	 */
	public function __construct(Application $application, $settings = null) {
		parent::__construct($application);
		$this->user_agent = null;
		$this->inherit_global_options();
		$settings = $this->call_hook("construct", $settings);
		if ($settings instanceof Request) {
			$this->initialize_from_request($settings);
		} elseif (is_array($settings) || is_string($settings)) {
			$this->initialize_from_settings($settings);
		} elseif ($settings === null) {
			$this->init = "not-inited";
		} else {
			throw new Exception_Parameter("{method} Requires array, Request, or null", array(
				"method" => __METHOD__,
			));
		}
	}

	/**
	 * Create a Request from PHP Superglobals $_SERVER, $_COOKIE, $_GET, $_REQUEST
	 *
	 * Supports PUT, POST, GET and POST with application/json Content-Type parsing of JSON
	 *
	 * @return self
	 */
	public function initialize_from_globals() {
		$this->data_raw = true;
		$this->data = null;
		$this->data_file = self::default_data_file;
		$this->data_inherit = null;

		$this->ip = $this->_find_remote_key($_SERVER);
		$this->remote_ip = avalue($_SERVER, 'REMOTE_ADDR');
		$this->server_ip = avalue($_SERVER, 'SERVER_ADDR');

		$this->set_method(avalue($_SERVER, 'REQUEST_METHOD', Net_HTTP::METHOD_GET));
		$this->uri = avalue($_SERVER, "REQUEST_URI", null);
		$this->headers = self::http_headers_from_server($_SERVER);
		$this->cookies = $_COOKIE;
		$this->url = $this->url_from_server($_SERVER);
		$this->files = is_array($_FILES) ? $_FILES : array();

		$this->url_parts = null;

		$this->variables = self::clean_gpc($this->default_request());

		$this->init = "globals";

		$this->call_hook(array(
			"initialize",
			"initialize_from_globals",
		));

		return $this;
	}

	/**
	 * Copy from another request
	 *
	 * @param Request $request
	 * @return self
	 */
	public function initialize_from_request(Request $request) {
		$this->method = $request->method;
		$this->uri = $request->uri;
		$this->headers = $request->headers;
		$this->cookies = $request->cookies;
		$this->variables = $request->variables;
		$this->files = $request->files;
		$this->url = $request->url;
		$this->url_parts = $request->url_parts;
		$this->data_raw = $request->data_raw; // Note: Loads data once if necessary
		$this->data = $request->data; // Note: Loads data once if necessary
		$this->data_inherit = $request;
		$this->data_file = $request->data_file;
		$this->ip = $request->ip;
		$this->remote_ip = $request->remote_ip;
		$this->server_ip = $request->server_ip;

		$this->init = "request";

		$this->call_hook(array(
			"initialize",
			"initialize_from_request",
		));

		return $this;
	}

	/**
	 * Initialze the object from settings (for mock objects)
	 *
	 * @param array|string $settings
	 * @throws Exception_Parameter
	 * @throws Exception_File_NotFound
	 * @return self
	 */
	public function initialize_from_settings($settings) {
		if (is_string($settings)) {
			$settings = array(
				"url" => $settings,
			);
		} elseif ($settings instanceof Request) {
			$this->_copy_from($settings);
			return;
		}
		if (!is_array($settings)) {
			throw new Exception_Parameter("Request constructor should take a string, array or Request {type} passed in settings {settings}", array(
				"type" => type($settings),
				"settings" => $settings,
			));
		}
		$method = $uri = $url = $data = $data_file = $data_raw = $ip = $remote_ip = $server_ip = null;
		$headers = $cookies = $variables = $files = array();
		extract($settings, EXTR_IF_EXISTS);
		$this->set_method(firstarg($method, "GET"));
		$this->uri = $uri;
		if (is_array($headers)) {
			foreach ($headers as $k => $v) {
				$this->header($k, $v);
			}
		}
		$this->cookies = is_array($cookies) ? $cookies : array();
		$this->variables = is_array($variables) ? $variables : array();
		$this->files = is_array($files) ? $files : array();
		$this->url = $url;
		$this->url_parts = null;
		if (!$this->uri) {
			$this->uri = $this->query() ? URL::query_format($this->path(), $this->query()) : $this->path();
		}
		if ($data_file) {
			if (!is_file($data_file)) {
				throw new Exception_File_NotFound($data_file, "Passed {filename} as settings to new Request {settings}", array(
					"settings" => $settings,
				));
			}
			$this->data_file = $data_file;
			$this->data_raw = true;
			$this->data = null;
		} else {
			$this->data = $data;
			$this->data_raw = $data_raw;
			$this->data_file = null;
		}
		$this->data_inherit = null;
		$this->ip = $ip;
		$this->remote_ip = $remote_ip ?? $ip;
		$this->server_ip = $server_ip;

		$this->init = "settings";
		$this->call_hook(array(
			"initialize",
			"initialize_from_settings",
		));

		return $this;
	}

	/**
	 * Is this request secure?
	 *
	 * @return boolean
	 */
	public function is_secure() {
		$this->_valid_url_parts();
		return avalue($this->url_parts, 'scheme') === 'https';
	}

	/**
	 * Retrieve the content type of the request
	 *
	 * @return string
	 */
	public function content_type() {
		$type = explode(";", $this->header(Net_HTTP::REQUEST_CONTENT_TYPE));
		return strtolower(array_shift($type));
	}

	/**
	 * Parse the accept header and return in priority order
	 *
	 * @return array
	 */
	public function parse_accept() {
		$name = Net_HTTP::REQUEST_ACCEPT;
		$result = $this->_parsed_header($name);
		if ($result) {
			return $result;
		}
		$accept = $this->header($name);
		if (!$accept) {
			return array(
				"*/*" => array(
					"q" => 1,
					"pattern" => "#[^/]+/[^/]+#",
				),
			);
		}
		$items = explode(",", preg_replace('/\s+/', '', $accept));
		foreach ($items as $item_index => $item) {
			$item_parts = explode(";", $item);
			$type = $subtype = "*";
			$attr = array(
				"weight" => 1,
			);

			foreach ($item_parts as $item_part) {
				$attr = array();
				if (strpos($item_part, '/') !== false) {
					list($type, $subtype) = explode('/', $item_part, 2);
					if (isset($attr['weight'])) {
						continue;
					}
					if ($type === "*") {
						$weight = 0;
					} elseif ($subtype === "*") {
						$weight = 0;
					} else {
						$weight = 1 + ($item_index * 0.01);
					}
					$attr['weight'] = $weight;
				} elseif (strpos($item_part, '=')) {
					list($name, $value) = explode('=', $item_part, 2);
					if ($name === 'q') {
						$value = floatval($value);
						$attr[$name] = $value;
						$attr['weight'] = $value;
					} else {
						$attr[$name] = $value;
					}
				}
			}
			$key = "$type/$subtype";
			$attr['pattern'] = '#' . strtr($key, array(
				"*" => "[^/]+",
				"+" => "\\+",
			)) . '#';
			$result[$key] = $attr;
		}
		uasort($result, "zesk_sort_weight_array");
		return $this->_parsed_header($name, $result);
	}

	/**
	 * Helper to determine best choice for response given the Accept header
	 *
	 * @param list $available_responses
	 * @return null|string
	 */
	public function accept_priority($available_responses) {
		$result = array();
		$accept = $this->parse_accept();
		foreach (to_list($available_responses) as $mime_type) {
			if (isset($accept[$mime_type])) {
				$result[$mime_type] = $accept[$mime_type];
				continue;
			}
			foreach ($accept as $key => $attr) {
				if (preg_match($attr['pattern'], $mime_type)) {
					$result[$mime_type] = $attr;
					break;
				}
			}
		}
		if (count($result) === 0) {
			return null;
		}
		if (count($result) > 1) {
			uasort($result, "zesk_sort_weight_array_reverse");
		}
		return first(array_keys($result));
	}

	/**
	 * Retrieve raw POST or PUT data from this request
	 *
	 * @todo Validate method vefore retrieving?
	 * @return array
	 */
	public function data() {
		if ($this->data_raw === true) {
			if ($this->data_inherit) {
				$this->data_raw = null;
				$this->data = $this->data_inherit->data();
			} else {
				$this->data_raw = file_get_contents($this->data_file);
			}
		}
		if ($this->data === null) {
			$content_type = StringTools::left($this->content_type(), ";");
			switch ($content_type) {
				case "application/json":
					$this->data = strlen($this->data_raw) > 0 ? JSON::decode($this->data_raw) : array();
					break;
				case "application/x-www-form-urlencoded":
					$this->data = array();
					parse_str($this->data_raw, $this->data);
					break;
				case "multipart/form-data":
					$this->data = $_REQUEST;
					break;
				default:
					$this->data = array();
					break;
			}
		}
		return $this->data;
	}

	/**
	 * Retrieve a header
	 *
	 * @param string $key
	 * @param string $value
	 * @return Ambigous <multitype:, string, array>|mixed|Request
	 */
	public function header($key = null, $value = null) {
		if ($key === null) {
			return ArrayTools::map_keys($this->headers, Net_HTTP::$request_headers);
		}
		if ($value === null) {
			return avalue($this->headers, strtolower($key), null);
		}
		$this->headers[strtolower($key)] = $value;
		unset($this->headers_parsed[strtolower($key)]);
		return $this;
	}

	/**
	 * Getter/setter for parsed header values
	 *
	 * @param string $key
	 * @param mixed $value
	 *        	Optional value to
	 * @return mixed|string
	 */
	private function _parsed_header($key, $value = null) {
		$key = strtolower($key);
		if ($value === null) {
			return isset($this->headers_parsed[$key]) ? $this->headers_parsed[$key] : null;
		}
		$this->headers_parsed[$key] = $value;
		return $value;
	}

	/**
	 * Is this a POST?
	 *
	 * @return boolean
	 */
	public function is_post() {
		return $this->method === Net_HTTP::METHOD_POST;
	}

	/**
	 * Is this an AJAX call?
	 *
	 * @deprecated 2018-03-29 Use prefer_json exclusively
	 *
	 * @return boolean
	 */
	public function is_ajax() {
		if ($this->getb('ajax')) {
			return true;
		}
		return $this->get("ajax_id", null) !== null;
	}

	/**
	 * Set or get the method for this request
	 *
	 * @param string $set
	 * @return Request|string
	 */
	public function method($set = null) {
		if ($set !== null) {
			$this->method = avalue(Net_HTTP::$methods, $set, $this->method);
			return $this;
		}
		return $this->method;
	}

	/**
	 * Set a variable associated with this request
	 *
	 * @param string $name
	 *        	Value to set
	 * @param string $value
	 *        	Value to set
	 * @param string $overwrite
	 *        	Overwrite value only if it's not set alrady
	 * @return mixed
	 */
	public function set($name, $value = null, $overwrite = true) {
		if (is_array($name)) {
			$result = array();
			foreach ($name as $k => $v) {
				$result[$k] = $this->set($k, $v, $overwrite);
			}
			return $result;
		}
		if (!$overwrite && array_key_exists($name, $this->variables)) {
			return false;
		}
		$this->variables[$name] = $value;
		if ($name === "router" && $value instanceof Router) {
			backtrace();
		}
		return $this->variables[$name];
	}

	/**
	 * Clean slashes from input values
	 *
	 * @param mixed $v
	 * @return mixed
	 */
	private static function _cleanslashes($v) {
		if (is_string($v)) {
			return stripslashes($v);
		}
		if (is_array($v)) {
			foreach ($v as $k => $x) {
				$v[$k] = self::_cleanslashes($x);
			}
		}
		return $v;
	}

	/**
	 * Get first occurrance of a value from the request variables
	 *
	 * @param array|string $names
	 *        	Array or list (;) of names of variables to look at
	 * @param string $default
	 *        	Dafault value if not found
	 *
	 * @return mixed
	 */
	public function get1($names, $default = null) {
		$names = to_list($names);
		foreach ($names as $name) {
			if ($this->has($name)) {
				return $this->get($name);
			}
		}
		return $default;
	}

	/**
	 * Retrieve a non-empty value from the request
	 *
	 * @param string $name
	 *        	Value to retrieve. Pass null to retrieve all non-empty values
	 * @param mixed $default
	 * @return array|mixed
	 */
	public function get_not_empty($name = null, $default = null) {
		if ($name === null) {
			return ArrayTools::clean($this->variables, array(
				"",
				null,
			));
		}
		return aevalue($this->variables, $name, $default);
	}

	/**
	 * Retrieve a variable value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return array|mixed
	 */
	public function get($name = null, $default = null) {
		if ($name === null) {
			return $this->variables;
		}
		return avalue($this->variables, $name, $default);
	}

	/**
	 * Does the request have this variable?
	 *
	 * @param string $name
	 * @param boolean $check_empty
	 * @return boolean
	 */
	public function has($name, $check_empty = false) {
		$name = strval($name);
		if (!array_key_exists($name, $this->variables)) {
			return false;
		}
		if (!$check_empty) {
			return true;
		}
		return !empty($this->variables[$name]);
	}

	/**
	 * Retrieve a variable as a boolean value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return boolean|mixed
	 */
	public function getb($name, $default = false) {
		return $this->get_bool($name, $default);
	}

	/**
	 * Retrieve a variable as a boolean value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return boolean|mixed
	 */
	public function get_bool($name, $default = false) {
		return to_bool($this->get($name), $default);
	}

	/**
	 * Retrieve a variable as a double value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return double|mixed
	 */
	public function getf($name, $default = false) {
		return to_double($this->get($name), $default);
	}

	/**
	 * Retrieve a variable as an integer value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return integer|mixed
	 */
	public function geti($name, $default = null) {
		return to_integer($this->get($name), $default);
	}

	/**
	 * Retrieve a value as an array value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @param string $sep
	 *        	For string values, split on this character
	 * @return array|mixed
	 */
	public function geta($name, $default = array(), $sep = ";") {
		$x = self::get($name, $default);
		if (is_array($x)) {
			return $x;
		}
		if (!is_string($x)) {
			$x = $default;
			if (is_array($x)) {
				return $default;
			}
		}
		if (is_string($x)) {
			if ($sep === "") {
				return str_split($x);
			}
			return explode($sep, $x);
		}
		return array();
	}

	/**
	 * Returns an array for an uploaded file
	 * Contains:
	 * - name - Original name
	 * - type - As provided by client
	 * - size - Size in bytes
	 * - tmp_name - Temporary file path on server of the file
	 * - error - The error associated with the upload
	 *
	 * @param string $name
	 * @return NULL array
	 */
	public function file($name, $index = 0) {
		$files = avalue($this->files, $name);
		if (!is_array($files)) {
			return null;
		}
		$error = avalue($files, 'error');
		if (is_array($error)) {
			$index = intval($index);
			foreach (to_list('name;type;tmp_name;error;size') as $k) {
				$result[$k] = avalue($files[$k], $index);
			}
			$files = $result;
			$files['index'] = $index;
			$files['total'] = count($error);
			$error = avalue($files, 'error');
		}
		if ($error !== null) {
			if ($error === UPLOAD_ERR_NO_FILE) {
				return null;
			}
			if ($error !== UPLOAD_ERR_OK) {
				throw new Exception_Upload($error);
			}
		}
		if (!avalue($files, 'size')) {
			// TODO Return error
			return null;
		}
		$path = $files['tmp_name'];
		if (!is_uploaded_file($path) && !avalue($files, 'zesk-daemon')) {
			return null;
		}
		return $files;
	}

	/**
	 * Retrieve all REQUEST variables for this request.
	 * Does not include object attributes such as URL or others.
	 *
	 * @see self::url_variables()
	 *
	 * @return number
	 */
	public function variables() {
		return $this->variables;
	}

	/**
	 * Get the URL, or set the URL and optionally the path
	 *
	 * @param string $set
	 * @param string $path
	 * @return Request|string
	 */
	public function url($set = null) {
		if ($set !== null) {
			$this->url = $set;
			$this->url_parts = null;
			$this->_valid_url_parts();
			$this->uri = $this->_derive_uri();
			return $this;
		}
		return $this->url;
	}

	/**
	 * Set the path on the server, updating the URL and parts
	 *
	 * @param string $set
	 * @return Request|string
	 */
	public function path($set = null) {
		if ($set !== null) {
			$this->_valid_url_parts();
			$this->url_parts['path'] = $set;
			$this->url(URL::unparse($this->url_parts));
			$this->uri = $this->_derive_uri();
			return $this;
		}
		return $this->url_variables('path');
	}

	/**
	 * Return path + query string (if supplied)
	 *
	 * @return string
	 */
	public function uri() {
		return $this->uri;
	}

	/**
	 * Retrieve a segment of the request path
	 *
	 * @param integer $index
	 * @param mixed $default
	 * @return string|mixed
	 */
	public function path_index($index, $default = null) {
		return avalue(explode("/", $this->path()), $index, $default);
	}

	/**
	 * Retrieve the current host
	 *
	 * @return string
	 */
	public function host() {
		$this->_valid_url_parts();
		return avalue($this->url_parts, 'host', null);
	}

	/**
	 * Retrieve the current port
	 *
	 * @return integer
	 */
	public function port() {
		$this->_valid_url_parts();
		return avalue($this->url_parts, 'port', URL::protocol_default_port($this->scheme()));
	}

	/**
	 * Retrieve the current port
	 *
	 * @return integer
	 */
	public function scheme() {
		$this->_valid_url_parts();
		return avalue($this->url_parts, 'scheme', 'http');
	}

	/**
	 * Retrieve the current query
	 *
	 * @return string
	 */
	public function query() {
		$this->_valid_url_parts();
		return avalue($this->url_parts, "query", null);
	}

	/**
	 * Retrieve the URL component
	 * @see Request::url_variables()
	 * @deprecated 2017-12
	 * @param string $component
	 * @return string
	 * @throws Exception_Key
	 */
	public function url_parts($component = null) {
		return $this->url_variables($component);
	}

	/**
	 * Retrieve the URL component
	 *
	 * @param string $component
	 * @return string
	 * @throws Exception_Key
	 */
	public function url_variables($component = null, $default = null) {
		$this->_valid_url_parts();
		if ($component === null) {
			return $this->url_parts;
		}
		if (array_key_exists($component, $this->url_parts)) {
			return $this->url_parts[$component];
		}
		return $default;
	}

	/**
	 * Universal getter
	 *
	 * @return mixed
	 */
	public function __get($key) {
		return $this->get($key);
	}

	/**
	 * Universal setter
	 *
	 * @return
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Parse the range value
	 *
	 * @todo make this an object, maybe?
	 * @return NULL
	 */
	public function range_parse() {
		$range = $this->header("Range");

		$matches = null;
		preg_match_all('/(-?[0-9]++(?:-(?![0-9]++))?)(?:-?([0-9]++))?/', $range, $matches, PREG_SET_ORDER);

		return $matches[0];
	}

	/**
	 * Calculates the byte range to use with send_file.
	 * If HTTP_RANGE doesn't
	 * exist then the complete byte range is returned
	 *
	 * @param integer $size
	 * @return array
	 * @todo Not used 2015-09-04
	 */
	protected function _calculate_byte_range($size) {
		$start = 0;
		$end = $size - 1;

		if (($range = $this->range_parse()) !== null) {
			$start = $range[1];
			if ($start[0] === '-') {
				$start = $size - abs($start);
			}
			if (isset($range[2])) {
				$end = $range[2];
			}
		}

		$start = abs(intval($start));
		$end = min(abs(intval($end)), $size - 1);
		$start = ($end < $start) ? 0 : max($start, 0);

		return array(
			$start,
			$end,
		);
	}

	/**
	 * Is this likely a web browser?
	 *
	 * @return boolean
	 */
	public function is_browser() {
		return $this->header(Net_HTTP::REQUEST_USER_AGENT) !== null;
	}

	/**
	 * Return user agent object
	 *
	 * @return Net_HTTP_UserAgent
	 */
	public function user_agent() {
		if (!$this->user_agent instanceof Net_HTTP_UserAgent) {
			$this->user_agent = new Net_HTTP_UserAgent($this->header(Net_HTTP::REQUEST_USER_AGENT));
		}
		return $this->user_agent;
	}

	/**
	 * Retrieve the IP address of the requestor, taking proxy server headers into consideration.
	 *
	 * @return mixed|NULL
	 */
	public function ip() {
		return $this->ip;
	}

	/**
	 * Retrieve the IP address of the requestor, ignoring any proxy servers
	 *
	 * @return mixed|NULL
	 */
	public function remote_ip() {
		return $this->remote_ip;
	}

	/**
	 * Retrieve the server IP address
	 *
	 * @return mixed|NULL
	 */
	public function server_ip() {
		return $this->server_ip;
	}

	/**
	 * Retrieve the referrer
	 *
	 * @return Ambigous
	 */
	public function referrer() {
		// $_SERVER["HTTP_REFERER"]
		return $this->header(Net_HTTP::REQUEST_REFERRER);
	}

	/**
	 *
	 * @param mixed $check
	 *        	Check value for user agent
	 * @return array
	 */
	public function user_agent_is($check = null) {
		return $this->user_agent()->is($check);
	}

	/**
	 * Output string value which can be passed to new Request($request->__toString())
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		return PHP::dump(array(
			"method" => $this->method,
			"uri" => $this->uri,
			"url" => $this->url,
			"data" => $this->data(),
			"headers" => $this->headers,
			"files" => $this->files,
			"cookies" => $this->cookies,
			"variables" => $this->variables,
		));
	}

	/**
	 *
	 * @see http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
	 * @return integer
	 */
	public static function max_upload_size($all = false) {
		$upload_max_filesize = to_bytes(ini_get('upload_max_filesize'), null);
		$post_max_size = to_bytes(ini_get('post_max_size'));
		$memory_limit = to_bytes(ini_get('memory_limit'));
		if ($all) {
			$result = compact("upload_max_filesize", "post_max_size", "memory_limit");
			$mink = $minv = null;
			foreach ($result as $key => $value) {
				if ($mink === null || $value < $minv) {
					$mink = $key;
					$minv = $value;
				}
			}
			return $result + array(
				"limiting_factor" => $mink,
			);
		}
		return min($upload_max_filesize, $post_max_size, $memory_limit);
	}

	/**
	 * Retrieve a cookie from the request
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed|array
	 */
	public function cookie($name = null, $default = null) {
		return $name === null ? $this->cookies : avalue($this->cookies, $name, $default);
	}

	/**
	 * Set the method, warning if unknown
	 *
	 * @param string $method
	 * @return Request
	 */
	private function set_method($method) {
		$method = strtoupper($method);
		if (!array_key_exists($method, Net_HTTP::$methods)) {
			throw new Exception_Parameter("Unknown method in {method_name}({method}", array(
				"method_name" => __METHOD__,
				"method" => $method,
			));
		}
		$this->method = $method;
		return $this;
	}

	/**
	 * Ensure that ->url_parts is available to be read
	 */
	private function _valid_url_parts() {
		if (is_array($this->url_parts)) {
			return;
		}
		$parts = URL::parse($this->url);
		if (!is_array($parts)) {
			$parts = array();
		}
		$this->url_parts = $parts + array(
			"url" => $this->url,
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => 80,
			'path' => '',
		);
	}

	/**
	 * Clean array of slashes if PHP is set up with magic_quotes_gpc
	 *
	 * @param array $mixed
	 * @return array
	 */
	private function clean_gpc(array $mixed) {
		return get_magic_quotes_gpc() ? self::_cleanslashes($mixed) : $mixed;
	}

	/**
	 * Retrieve the default request
	 *
	 * @return array
	 */
	private function default_request() {
		if ($this->method === Net_HTTP::METHOD_PUT) {
			// Support JSON
			return $this->data() + $_GET;
		}
		if ($this->method === Net_HTTP::METHOD_POST) {
			// Support JSON
			return $this->data() + $_GET;
		}
		return is_array($_REQUEST) ? $_REQUEST : array();
	}

	/**
	 * Convert server variables into HTTP headers
	 *
	 * @param array $server
	 * @return array
	 */
	private static function http_headers_from_server(array $server) {
		$server = ArrayTools::kreplace(array_change_key_case($server), "_", '-');
		$headers = array();
		foreach ($server as $key => $value) {
			foreach (array(
				"http-" => true,
				"content-" => false,
			) as $prefix => $unprefix) {
				$len = strlen($prefix);
				if (substr($key, 0, $len) === $prefix) {
					$headers[$unprefix ? substr($key, $len) : $key] = $value;
				}
			}
		}
		return $headers;
	}

	private function _derive_uri() {
		return $this->query() ? URL::query_format($this->path() . $this->query()) : $this->path();
	}

	private function url_from_server($server) {
		$parts['scheme'] = $this->current_scheme($server);
		$parts['host'] = $this->current_host();
		$parts['port'] = $this->current_port($server);
		$parts['path'] = $this->current_uri($server);
		return URL::unparse($parts);
	}

	private function current_scheme(array $server) {
		// Amazon load balancers
		$proto = $this->header("X-Forwarded-Proto");
		if ($proto) {
			return $proto;
		}
		return avalue($server, 'HTTPS') === "on" ? "https" : "http";
	}

	private function current_host() {
		$host = $this->header("Host");
		return strtolower(StringTools::left($host, ":", $host));
	}

	private function current_port(array $server) {
		// Amazon load balancers
		$port = $this->header("X-Forwarded-Port");
		if ($port) {
			return intval($port);
		}
		return avalue($server, "SERVER_PORT", 80);
	}

	private function current_uri(array $server) {
		return avalue($server, 'REQUEST_URI');
	}

	/**
	 * Does the current request prefer a JSON response?
	 *
	 * @return boolean
	 */
	public function prefer_json() {
		if ($this->is_ajax()) {
			return true;
		}
		return $this->accept_priority(array(
			"application/json",
			"text/html",
		)) === "application/json";
	}

	/**
	 * Helper function for self::remote.
	 * Searches an array for a valid IP address.
	 *
	 * @param array $arr
	 *        	An array to search for certain keys
	 * @return an IP address if found, or false
	 */
	private static function _find_remote_key(array $server, $default = null) {
		$ks = array(
			"HTTP_CLIENT_IP",
			"HTTP_X_FORWARDED_FOR",
			"REMOTE_ADDR",
		);
		foreach ($ks as $k) {
			if (!isset($server[$k])) {
				continue;
			}
			$ip = $server[$k];
			if ($ip === "unknown") {
				continue;
			}
			if (empty($ip)) {
				continue;
			}
			$match = false;
			if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip, $match)) {
				return $match[0];
			}
		}
		return $default;
	}
}
