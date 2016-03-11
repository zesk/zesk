<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/response.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

/**
 * Abstraction for web server responses to Request
 *
 * @see Request
 * @see Response_HTML
 * @package zesk
 * @subpackage system
 */
class Response extends Hookable {
	const content_type_json = "application/json";
	const content_type_html = "text/html";
	const cache_scheme = 1;
	const cache_query = 2;
	const cache_path = 3;
	
	/**
	 * Application associated with this response
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * Request associated with this response
	 * 
	 * @var Request
	 */
	public $request = null;
	
	/**
	 * Content to return (if small enough)
	 * 
	 * @var string
	 */
	public $content = null;
	
	/**
	 * File to return (for big stuff)
	 * 
	 * @var string
	 */
	protected $content_file = null;
	
	/**
	 * Status code
	 * 
	 * @var integer
	 */
	public $status_code = Net_HTTP::Status_OK;
	
	/**
	 * Status message
	 * 
	 * @var string
	 */
	public $status_message = "OK";
	
	/**
	 * Content-Type header
	 * 
	 * @var string
	 */
	public $content_type = null;
	
	/**
	 * Content-Type header
	 * 
	 * @var string
	 */
	public $charset = null;
	
	/**
	 * Headers.
	 * Key is always properly cased header. Values may be multi-array or string.
	 * 
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Name/value data passed back to client if response type supports it.
	 *
	 * @var array
	 */
	protected $response_data = array();
	
	/**
	 * Map of low-header to properly cased headers
	 * 
	 * @var array
	 */
	protected $headers_cased = array(
		'p3p' => "P3P",
		'content-disposition' => "Content-Disposition"
	);
	
	/**
	 * Flag to indicate that this object is currently rendering.
	 * Avoids infinite loops.
	 * 
	 * @var boolean
	 */
	private $rendering = false;
	
	/**
	 * Cache responses to the request
	 *
	 * @var unknown
	 */
	private $cache_settings = null;
	
	/**
	 * Singleton instance of the Response
	 * 
	 * @var Response
	 */
	static $instance = null;
	
	/**
	 * Retrieve global Response instance
	 * 
	 * @param string $mixed
	 *        	URL of Response. If not specified takes from url::current. If Response, then sets
	 *        	Response
	 *        	instance to $mixed
	 * @param array $Response
	 *        	server Response name/value pairs. If not specified, uses $_Response
	 * @param boolean $is_post
	 *        	Whether this is a POST operation or not. If not specified, uses self::_is_post
	 * @return Response
	 */
	public static function instance($application = null, $content_type = null) {
		if (!$application instanceof Application) {
			zesk::deprecated();
			$application = Application::instance();
		}
		if ($application->response) {
			return $application->response;
		}
		$application->response = self::factory($application, $content_type);
		return $application->response;
	}
	public static function factory(Application $application, $options = null) {
		$content_type = zesk::get("Response::content_type", self::content_type_html);
		if (is_string($options)) {
			$content_type = $options;
			$options = array();
		} else if (is_array($options)) {
			$content_type = aevalue($options, 'content type', $content_type);
		} else {
			$options = array();
		}
		try {
			$class = "Response_" . str_replace("/", "_", $content_type);
			return zesk::factory($class, $application, $content_type);
		} catch (Exception_Class_NotFound $e) {
			return new Response_HTML($application, self::content_type_html);
		}
	}
	function __construct(Application $application, $options = null) {
		$this->application = $application;
		$this->request = $application->request;
		parent::__construct($options);
		$this->inherit_global_options();
	}
	function status($error_code, $error_string = null) {
		$codes = Net_HTTP::$status_text;
		$code = array_key_exists($error_code, $codes) ? $error_code : 500;
		if ($error_string === null) {
			$error_string = avalue($codes, $code);
		}
		$this->status_code = $code;
		$this->status_message = $error_string;
		return $this;
	}
	public function redirect_message_clear() {
		Session::instance()->redirect_message = null;
	}
	public function redirect_message($message = null) {
		$session = Session::instance();
		if (!$session) {
			return array();
		}
		$messages = to_array($session->redirect_message);
		if ($message === null) {
			return $messages;
		}
		if (empty($message)) {
			return $this;
		}
		if (is_array($message)) {
			foreach ($message as $m) {
				if (!empty($m)) {
					$messages[md5($m)] = $m;
				}
			}
		} else {
			$messages[md5($message)] = $message;
		}
		$session->redirect_message = $messages;
		return $this;
	}
	
	/**
	 * Set up redirect debugging
	 *
	 * @param mixed $set        	
	 * @return self|boolean
	 */
	public function debug_redirect($set = null) {
		if ($set === null) {
			return $this->option_bool('debug_redirect');
		}
		return $this->set_option('debug_redirect', to_bool($set));
	}
	private function _header($string) {
		if ($this->cache_settings) {
			$this->cache_settings['headers'][] = $string;
		}
		header($string);
	}
	
	/**
	 * Redirect to a URL, optionally adding a message to the resulting URL
	 * 
	 * @param string $u
	 *        	URL to redirect to.
	 * @param unknown_type $message        	
	 * @see Response::redirect_default
	 */
	public function redirect($url, $message = null) {
		$saved_url = $url;
		/* Clean out any unwanted characters from the URL */
		$url = preg_replace("/[\x01-\x1F\x7F-\xFF]/", '', $url);
		if ($message !== null) {
			$this->redirect_message($message);
		}
		$url = $this->hook('redirect_alter', $url);
		$content = "";
		if ($this->option_bool("debug_redirect")) {
			$content = html::a($url, $url);
			log::debug(_backtrace());
			$content = Application::instance()->theme("response/redirect", array(
				'request' => $this->request,
				'response' => $this,
				'content' => $content,
				'url' => $url,
				'original_url' => $saved_url
			));
		} else {
			if ($url) {
				$this->_header("Location: $url");
			}
			// Should do a response/redirect.tpl type thing here, too, in non-dev mode?
			// TODO
			zesk::hook_array('headers', array(
				$this->request,
				$this,
				null
			));
		}
		echo $content;
		$this->cache_save($content);
		ob_flush_all();
		exit();
	}
	function redirect_default($url, $message = null) {
		$ref = $this->request->get("ref", "");
		if ($ref != "") {
			$url = $ref;
		}
		$this->redirect($url, $message);
	}
	private function response_headers() {
		static $called = false;
		
		$this->hook('headers');
		if ($called) {
			throw new Exception_Semantics("Response headers called twice! {previous}", array(
				"previous" => $called
			));
		} else {
			$called = zesk::calling_function(2);
		}
		$file = $line = null;
		if (headers_sent($file, $line)) {
			throw new Exception_Semantics("Headers already sent on {file}:{line}", array(
				"file" => $file,
				"line" => $line
			));
		}
		$this->hook("headers");
		if (begins($this->content_type, "text/")) {
			if (empty($this->charset)) {
				$this->charset = "utf-8";
			}
			$content_type = $this->content_type . "; charset=" . $this->charset;
		} else {
			$content_type = $this->content_type;
		}
		if (Application::instance()->development() && zesk::getb('application/json-text/html')) {
			if (in_array($this->content_type, array(
				self::content_type_json
			))) {
				$content_type = "text/html; charset=" . $this->charset;
			}
		}
		$code = $this->status_code;
		if ($code !== Net_HTTP::Status_OK) {
			$message = $this->status_message;
			$message = $message ? $message : avalue(Net_HTTP::$status_text, $code, "No error message");
			$this->_header("HTTP/1.0 " . $this->status_code . " " . $message);
		}
		$this->_header("Content-Type: " . $content_type);
		foreach ($this->headers as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$this->_header("$name: $v");
				}
			} else {
				$this->_header("$name: $value");
			}
		}
	}
	final function html($set = null) {
		if ($set !== null) {
			$this->content_type = 'text/html';
			return $this;
		}
		return $this->content_type === 'text/html';
	}
	final function file($file = null) {
		if ($file === null) {
			return $this->content_file;
		}
		if (!file_exists($file)) {
			throw new Exception_File_NotFound($file);
		}
		$this->content_type = mime::from_filename($file);
		$this->header("Last-Modified", gmdate('D, d M Y H:i:s \G\M\T', filemtime($file)));
		$this->header("Content-Length", filesize($file));
		$this->content = null;
		$this->content_file = $file;
		return $this;
	}
	
	/**
	 * TODO Move this to Response
	 */
	final public function p3p($policyref, $compact_p3p = null) {
		if (empty($compact_p3p)) {
			$compact_p3p = "NOI DSP NID PSA ADM OUR IND NAV COM";
		}
		if (strpos($compact_p3p, 'CP=') === false) {
			$compact_p3p = "CP=\"$compact_p3p\"";
		}
		return $this->header("P3P", "policyref=\"$policyref\", $compact_p3p");
	}
	final public function nocache() {
		$this->header("Cache-Control", "no-cache");
		$this->header("Pragma", "no-cache");
		$this->header("Expires", "-1");
		return $this;
	}
	final public function download($file, $name = null, $type = null) {
		ignore_user_abort(1);
		ini_set("max_execution_time", 5000 /* seconds */);
		if ($name === null) {
			$name = basename($file);
		}
		$name = file::name_clean($name);
		if ($type === null) {
			$type = "attachment";
		}
		return $this->file($file)->header("Content-Disposition", "$type; filename=\"$name\"")->nocache();
	}
	final public function content_type($set = null) {
		if ($set !== null) {
			$this->content_type = $set;
			return $this;
		}
		return $this->content_type;
	}
	
	/**
	 * Return "extra" json data, only passed back to client on request types which support it.
	 *
	 * Call modes:
	 *
	 * <code>
	 * $current_data = $response->response_data();
	 * $response->response_data(array("message" => "Hello, world!")); // Adds to current response
	 * data
	 * $response->response_data(array("message" => "Hello, world!"), false); // Replaces current
	 * response data
	 * </code>
	 *
	 * @param array $extras        	
	 * @param string $add        	
	 * @return array|Response
	 */
	final public function response_data(array $data = null, $add = true) {
		if ($data === null) {
			return $this->response_data;
		}
		$this->response_data = $add ? $data + $this->response_data : $data;
		return $this;
	}
	
	/**
	 * Return JSON data
	 *
	 * @param string $set        	
	 * @return Response|boolean
	 */
	final public function json($set = null) {
		if ($set !== null) {
			$this->content_type = self::content_type_json;
			if (count($this->response_data) === 0) {
				$content = $set;
			} else {
				if (is_array($set)) {
					$content = $set + $this->response_data;
				} else {
					$content = array(
						'content' => $set
					) + $this->response_data;
				}
			}
			$this->content = is_array($this->content) ? $content + $this->content : $content;
			return $this;
		}
		return $this->content_type === self::content_type_json;
	}
	
	/**
	 * Set a date header
	 * 
	 * @param string $name
	 *        	Header to set (Expires, Date, Last-Modified, etc.)
	 * @param mixed $value
	 *        	Timestamp or integer
	 */
	final public function header_date($name, $value) {
		if ($value instanceof Timestamp) {
			$value = $value->unix_timestamp();
		}
		return $this->header($name, gmdate('D, d M Y H:i:s \G\M\T', $value));
	}
	
	/**
	 * Setter/Getter for headers
	 * 
	 * @param string $name
	 *        	Name of header to get/set
	 * @param string $value
	 *        	Value of header to set
	 * @return mixed All headers if name is null, header value if name is set, $this if name and
	 *         value are set
	 */
	final public function header($name = null, $value = null) {
		if ($name === null) {
			return $this->headers;
		}
		$lowname = strtolower($name);
		if ($lowname === "content-type") {
			if ($value === null) {
				return $this->content_type;
			}
			$this->content_type = $value;
			return $this;
		}
		if (!array_key_exists($lowname, $this->headers_cased)) {
			if ($value === null) {
				return null;
			}
			$this->headers_cased[$lowname] = $name;
		} else {
			$name = $this->headers_cased[$lowname];
		}
		if ($value === null) {
			return avalue($this->headers, $name);
		}
		$this->headers[$name] = $value;
		return $this;
	}
	private function _render_content() {
		if ($this->content_type === self::content_type_json && !is_string($this->content)) {
			$this->content = json::encode($this->content);
		}
		return $this->content;
	}
	
	/**
	 * Return response
	 * 
	 * @return string
	 */
	final function render() {
		if ($this->rendering) {
			return "";
		}
		$this->rendering = true;
		$this->hook("render");
		if ($this->content_file) {
			if ($this->content_type === self::content_type_html) {
				$result = str_replace("\0", " ", file_get_contents($this->content_file));
			} else {
				$result = file_get_contents($this->content_file);
			}
		} else {
			$result = $this->_render_content();
		}
		$this->response_headers();
		$this->hook("rendered", $result);
		$this->rendering = false;
		return $result;
	}
	
	/**
	 * Echo response
	 * 
	 * @return void
	 */
	final function output(array $options = array()) {
		if ($this->rendering) {
			return;
		}
		$this->rendering = true;
		if (!avalue($options, 'skip-headers')) {
			$this->response_headers();
		}
		$this->hook("output");
		if ($this->content_file) {
			/*
			 * This is an obscure error in MSIE which dies if a null is found mid-stream. Also obscure error where
			 * fpassthru doesn't passthru everything/terminates
			 */
			$f = fopen($this->content_file, "rb");
			if ($this->content_type === self::content_type_html) {
				while (!feof($f)) {
					print str_replace("\0", " ", fread($f, 8192)); // Keep length the same
				}
			} else {
				while (!feof($f)) {
					print fread($f, 8192);
				}
			}
			flush();
			fclose($f);
		} else {
			echo $this->_render_content();
		}
		$this->hook("outputted");
		$this->rendering = false;
	}
	public function to_json() {
		return $this->response_data;
	}
	
	/**
	 * Cache settings for this request
	 *
	 * "seconds" - For how many seconds
	 * "parts" - Url parts to match
	 *
	 * @param array $options        	
	 * @param boolean $append        	
	 * @return array|Response
	 */
	public function cache(array $options = null, $append = true) {
		if ($options === null) {
			return $this->cache_settings;
		}
		if (!is_array($this->cache_settings)) {
			$this->cache_settings = array();
		}
		$this->cache_settings = $append ? $options + $this->cache_settings : $options;
		return $this;
	}
	public function cache_forever() {
		return $this->cache(array(
			"seconds" => 1576800000
		));
	}
	public function cache_for($seconds, $level = self::cache_scheme) {
		return $this->cache(array(
			"seconds" => intval($seconds),
			"level" => $level
		));
	}
	
	/**
	 * Ordered from most specific to least specific
	 * 
	 * @var string
	 */
	private static $cache_pattern = array(
		self::cache_scheme => "{scheme}/{host}_{port}{path}/{query}",
		self::cache_query => "any/{host}_{port}{path}/{query}",
		self::cache_path => "any/{host}_{port}{path}"
	);
	
	/**
	 * Convert URL into standard parts with defaults
	 * 
	 * @param string $url        	
	 * @return array
	 */
	private static function _cache_parts($url) {
		$parts = url::parse($url);
		$parts += array(
			"port" => url::protocol_default_port($parts['scheme']),
			"scheme" => 'none',
			"host" => '_host_',
			"query" => '_query_',
			"path" => '_path_'
		);
		return $parts;
	}
	
	/**
	 * Is content type?
	 *
	 * @param mixed $mixed
	 *        	String or list
	 * @return boolean
	 */
	public function is_content_type($mixed) {
		foreach (to_list($mixed) as $type) {
			if (strpos($this->content_type, $type) !== false) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Save this response's content, if the page requested to be cached
	 *
	 * @param string $content
	 *        	Contents to save
	 * @return boolean
	 */
	public function cache_save($content) {
		if ($this->cache_settings === null) {
			return false;
		}
		
		$parts = self::_cache_parts($this->request->url());
		$level = self::cache_scheme;
		$seconds = $expires = null;
		$headers = array();
		extract($this->cache_settings, EXTR_IF_EXISTS);
		$pattern = avalue(self::$cache_pattern, $level, self::$cache_pattern[self::cache_scheme]);
		$cache = Cache::register(map($pattern, $parts));
		$cache->headers = $headers;
		$cache->content = $content;
		if ($seconds !== null) {
			$expires = time() + $seconds;
		}
		if ($expires !== null) {
			$cache->expires = $expires;
		}
		return true;
	}
	public static function cached($url) {
		$parts = self::_cache_parts($url);
		foreach (self::$cache_pattern as $level => $id) {
			$id = map($id, $parts);
			if (($cache = Cache::find($id)) !== null) {
				$expires = $cache->expires;
				if ($expires !== null && time() > $expires) {
					return null;
				}
				foreach ($cache->headers as $header) {
					header($header);
				}
				return $cache->content;
			}
		}
		return null;
	}
}
