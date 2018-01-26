<?php

/**
 *
 */
namespace zesk;

use Psr\Cache\CacheItemPoolInterface;
use zesk\Response\HTML as HTMLResponse;
use zesk\Response\JSON;
use zesk\Response\Text;
use zesk\Response\Raw;

/**
 * Abstraction for web server responses to Request
 *
 * @see Request
 * @see zesk\Response\HTML
 * @see zesk\Response\JSON
 * @see zesk\Response\Text
 * @package zesk
 * @subpackage system
 */
class Response extends Hookable {
	/**
	 * @deprecated 2018-01
	 *
	 * @var string
	 */
	const content_type_json = "application/json";
	/**
	 * @deprecated 2018-01
	 *
	 * @var string
	 */
	const content_type_html = "text/html";
	/**
	 * @deprecated 2018-01
	 *
	 * @var string
	 */
	const content_type_plaintext = "text/plain";
	/**
	 * @deprecated 2018-01
	 * @var string
	 */
	const content_type_raw = "application/octet-stream";

	/**
	 * @var string
	 */
	const CONTENT_TYPE_JSON = "application/json";
	/**
	 *
	 * @var string
	 */
	const CONTENT_TYPE_HTML = "text/html";
	/**
	 *
	 * @var string
	 */
	const CONTENT_TYPE_PLAINTEXT = "text/plain";
	/**
	 *
	 * @var string
	 */
	const CONTENT_TYPE_RAW = "application/octet-stream";
	/**
	 *
	 * @var integer
	 */
	const CACHE_SCHEME = 1;
	/**
	 *
	 * @var integer
	 */
	const CACHE_QUERY = 2;
	/**
	 *
	 * @var integer
	 */
	const CACHE_PATH = 3;

	/**
	 * Ordered from most specific to least specific
	 *
	 * @var string
	 */
	private static $cache_pattern = array(
		self::CACHE_SCHEME => "{scheme}/{host}_{port}{path}/{query}",
		self::CACHE_QUERY => "any/{host}_{port}{path}/{query}",
		self::CACHE_PATH => "any/{host}_{port}{path}"
	);

	/**
	 * Cache responses to the request
	 *
	 * @var unknown
	 */
	private $cache_settings = null;

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
	static $type_classes = array(
		self::content_type_html => HTMLResponse::class,
		self::CONTENT_TYPE_JSON => JSON::class,
		self::CONTENT_TYPE_PLAINTEXT => Text::class,
		self::CONTENT_TYPE_RAW => Raw::class
	);
	/**
	 *
	 * @var zesk\Response\Type[]
	 */
	protected $types = array();

	/**
	 * Page title
	 *
	 * @var string
	 */
	protected $title = "";

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
	 * @todo This exists in Net_HTTP I think -KMD 2018-01
	 *
	 * @var array
	 */
	protected $headers_cased = array(
		'content-disposition' => "Content-Disposition"
	);

	/**
	 * ID counter for rendering things on the page which should have unique IDs
	 *
	 * @var integer
	 */
	private $id_counter = 0;
	/**
	 * Flag to indicate that this object is currently rendering.
	 * Avoids infinite loops.
	 *
	 * @var boolean
	 */
	private $rendering = false;

	/**
	 * Retrieve global Response instance
	 *
	 * @return Response
	 */
	/**
	 *
	 * @param \zesk\Application $application
	 * @param string $content_type
	 * @return \zesk\Response_Text_HTML
	 */
	public static function instance(Application $application, $content_type = null) {
		if ($application->response) {
			return $application->response;
		}
		$application->response = self::factory($application, $content_type);
		return $application->response;
	}

	/**
	 * Handle deprecated configuration
	 *
	 * @param Kernel $kernel
	 */
	public static function hooks(Application $application) {
		// Not sure when, let's say 2017-03
		$application->configuration->deprecated("Response", __CLASS__);
		$application->configuration->deprecated("Response_Text_HTML", __CLASS__);
	}
	/**
	 *
	 * @param Application $application
	 * @param unknown $options
	 * @return \zesk\stdClass|\zesk\Response_Text_HTML
	 */
	public static function factory(Application $application, $options = null) {
		$content_type = $application->configuration->path(__CLASS__)->get("content_type", self::content_type_html);
		if (is_string($options)) {
			$content_type = $options;
			$options = array();
		} else if (is_array($options)) {
			$content_type = aevalue($options, 'content type', $content_type);
		} else {
			$options = array();
		}
		return $application->objects->factory(__CLASS__, $application, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $options
	 */
	function __construct(Application $application, array $options = array()) {
		$this->request = $application->request;
		parent::__construct($application, $options);
		$this->inherit_global_options();
	}

	/**
	 *
	 * @param unknown $error_code
	 * @param unknown $error_string
	 * @return \zesk\Response
	 */
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

	/**
	 * These are not saved as part of cached headers, generally speaking
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $options
	 * @return unknown
	 */
	public function cookie($name, $value = null, array $options = array()) {
		$expire = avalue($options, 'expire', $this->option("cookie_expire"));
		if ($expire instanceof Timestamp) {
			$n_seconds = $expire->subtract(Timestamp::now($expire->time_zone()));
		} else if (is_integer($expire)) {
			$n_seconds = $expire;
		} else {
			$n_seconds = null;
		}
		$host = $this->request->host();
		$domain = avalue($options, 'domain', $this->option("cookie_domain"));
		if ($domain) {
			$domain = ltrim($domain, ".");
			if (!ends($host, $domain)) {
				$this->application->logger->warning("Unable to set cookie domain {cookie_domain} on host {host}", array(
					"cookie_domain" => $domain,
					"host" => $host
				));
				$domain = null;
			}
		}
		$secure = avalue($options, 'secure', $this->option_bool("cookie_secure"));
		$path = avalue($options, 'path', $this->option_bool("cookie_path", "/"));
		if (!$domain) {
			$domain = Domain::domain_factory($this->application, $host)->compute_cookie_domain();
		}
		$expire_time = $n_seconds ? time() + $n_seconds : null;
		if ($this->request->is_browser()) {
			setcookie($name, null);
			if (!empty($value)) {
				setcookie($name, $value, $expire_time, $path, ".$domain", $secure);
			}
		}
		return $this;
	}

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 */
	public function redirect_message_clear() {
		$this->application->session()->redirect_message = null;
	}

	/**
	 *
	 * @todo Move this elsewhere. Response addon?
	 * @param string $message
	 * @return \zesk\Response
	 */
	public function redirect_message($message = null) {
		try {
			$session = $this->application->session();
		} catch (\Exception $e) {
			return array();
		}
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

	/**
	 * Output a header
	 *
	 * @param string $string
	 *        	Complete header line (e.g. "Location: /failed")
	 */
	private function _header($string) {
		if ($this->cache_settings) {
			$this->cache_settings['headers'][] = $string;
		}
		header($string);
	}

	/**
	 *
	 *
	 *       Redirect to a URL, optionally adding a message to the resulting URL
	 *
	 * @param string $u
	 *        	URL to redirect to.
	 * @param unknown_type $message
	 * @see Response::redirect_default
	 * @todo Move this into a plugin or something; shouldn't be here
	 */
	public function redirect($url, $message = null) {
		$saved_url = $url;
		/* Clean out any unwanted characters from the URL */
		$url = preg_replace("/[\x01-\x1F\x7F-\xFF]/", '', $url);
		if ($message !== null) {
			$this->redirect_message($message);
		}
		$url = $this->call_hook('redirect_alter', $url);
		$content = "";
		if ($this->option_bool("debug_redirect")) {
			$content = HTML::a($url, $url);
			$content = $this->application->theme("response/redirect", array(
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
			$this->application->hooks->call_arguments('headers', array(
				$this->request,
				$this,
				null
			));
		}
		echo $content;
		$this->cache_save($content);
		// flush all output buffering
		while (ob_get_level() > 0) {
			ob_end_flush();
		}
		// TODO - Should we unplug the app like this?
		exit();
	}

	/**
	 *
	 * @throws Exception_Semantics
	 */
	private function response_headers() {
		static $called = false;

		$this->call_hook('headers');
		if ($this->option_bool("skip_response_headers")) {
			return;
		}
		if ($called) {
			throw new Exception_Semantics("Response headers called twice! {previous}", array(
				"previous" => $called
			));
		} else {
			$called = calling_function(2);
		}
		$file = $line = null;
		if (headers_sent($file, $line)) {
			throw new Exception_Semantics("Headers already sent on {file}:{line}", array(
				"file" => $file,
				"line" => $line
			));
		}
		$this->call_hook("headers");
		if (begins($this->content_type, "text/")) {
			if (empty($this->charset)) {
				$this->charset = "utf-8";
			}
			$content_type = $this->content_type . "; charset=" . $this->charset;
		} else {
			$content_type = $this->content_type;
		}
		if ($this->application->development() && $this->application->configuration->path_get(array(
			__CLASS__,
			"json_to_html"
		))) {
			if (in_array($this->content_type, array(
				self::CONTENT_TYPE_JSON
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

	/**
	 * If ref is passed in by request, redirect to that location, otherwise, redirect to passed in
	 * URL
	 *
	 * @param string $url
	 * @param string $message
	 *        	Already-localized message to display to user on redirected page
	 */
	function redirect_default($url, $message = null) {
		$ref = $this->request->get("ref", "");
		if ($ref != "") {
			$url = $ref;
		}
		$this->redirect($url, $message);
	}

	/**
	 * Is this content type text/html?
	 *
	 * @param string $set
	 * @return \zesk\Response|boolean
	 */
	final function is_html($set = null) {
		if ($set !== null) {
			$this->content_type = self::content_type_html;
			return $this;
		}
		return $this->content_type === self::content_type_html;
	}

	/**
	 * Is this content type text/html?
	 *
	 * @param string $set
	 * @return \zesk\Response|boolean
	 */
	final function is_json($set = null) {
		if ($set !== null) {
			$this->content_type = self::CONTENT_TYPE_JSON;
			return $this;
		}
		return $this->content_type === self::CONTENT_TYPE_JSON;
	}

	/**
	 * Do not cache this page
	 *
	 * @return \zesk\Response
	 */
	final public function nocache() {
		$this->cache_settings = null;
		$this->header("Cache-Control", "no-cache, must-revalidate");
		$this->header("Pragma", "no-cache");
		$this->header("Expires", "-1");
		return $this;
	}

	/**
	 * Getter/setter for content type of this response
	 *
	 * @param string $set
	 * @return \zesk\Response|string
	 */
	final public function content_type($set = null) {
		if ($set !== null) {
			$this->content_type = $set;
			return $this;
		}
		return $this->content_type;
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

	/**
	 * Set/get page title
	 *
	 * @param string $set
	 * @param string $overwrite
	 * @return string
	 */
	function title($set = null, $overwrite = true) {
		if ($set !== null) {
			if ($overwrite || $this->title === "") {
				$this->title = (string) $set;
				$this->application->logger->debug("Set title to \"$set\"");
			} else {
				$this->application->logger->debug("Failed to set title to \"$set\"");
			}
			return $this;
		}
		return $this->title;
	}

	/**
	 *
	 * @return string
	 */
	private function _render_content() {
		if (isset($this->types[$this->content_type])) {
			return $this->types[$this->content_type]->render($this->content);
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
		$this->call_hook("render");
		if ($this->content_file) {
			if ($this->content_type === self::content_type_html) {
				$result = str_replace("\0", " ", file_get_contents($this->content_file));
			} else {
				$result = file_get_contents($this->content_file);
			}
		} else {
			$result = $this->_render_content();
			$result = $this->call_hook("content_postprocess", $result);
		}
		$this->response_headers();
		$this->call_hook("rendered", $result);
		$this->rendering = false;
		return $result;
	}

	/**
	 * Echo response
	 *
	 * @return void
	 */
	public function output(array $options = array()) {
		if ($this->rendering) {
			return;
		}
		$this->rendering = true;
		if (!avalue($options, 'skip-headers')) {
			$this->response_headers();
		}
		$this->call_hook("output");
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
		$this->call_hook("outputted");
		$this->rendering = false;
	}

	/**
	 *
	 * @return array
	 */
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

	/**
	 *
	 * @return \zesk\Response
	 */
	public function cache_forever() {
		return $this->cache(array(
			"seconds" => 1576800000
		));
	}

	/**
	 * Cache for n seconds
	 *
	 * @param integer $seconds
	 *        	Number of seconds to cache this content
	 * @param integer $level
	 *        	What cache pattern to use to store this content
	 * @return \zesk\Response
	 */
	public function cache_for($seconds, $level = self::CACHE_SCHEME) {
		return $this->cache(array(
			"seconds" => intval($seconds),
			"level" => $level
		));
	}

	/**
	 * Convert URL into standard parts with defaults
	 *
	 * @param string $url
	 * @return array
	 */
	private static function _cache_parts($url) {
		$parts = to_array(URL::parse($url)) + array(
			"scheme" => "none"
		);
		$parts += array(
			"port" => URL::protocol_default_port($parts['scheme']),
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
	 * Retrieve a cached respose
	 *
	 * @param CacheItemPoolInterface $pool
	 * @param string $id
	 * @return \Psr\Cache\CacheItemInterface
	 */
	private static function fetch_cache_id(CacheItemPoolInterface $pool, $id) {
		return $pool->getItem(__CLASS__ . "::" . $id);
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
		$level = self::CACHE_SCHEME;
		$seconds = $expires = null;
		$headers = array();
		extract($this->cache_settings, EXTR_IF_EXISTS);
		$pattern = avalue(self::$cache_pattern, $level, self::$cache_pattern[self::CACHE_SCHEME]);

		$item = self::fetch_cache_id($this->application->cache, map($pattern, $parts));
		$value = new \stdClass();
		$value->headers = $headers;
		$value->content = $content;
		if ($seconds !== null) {
			$expires = time() + $seconds;
		}
		if ($expires !== null) {
			$item->expiresAfter($expires);
		}
		$this->application->cache->save($item->set($value));
		return true;
	}

	/**
	 * If content for URL is cached, invoke headers and return content.
	 *
	 * Returns null if cache item not found
	 *
	 * @param string $url
	 * @return NULL|string
	 */
	public static function cached(CacheItemPoolInterface $pool, $url) {
		$parts = self::_cache_parts($url);
		foreach (self::$cache_pattern as $level => $id) {
			$id = map($id, $parts);
			$item = self::fetch_cache_id($pool, $id);
			if ($item->isHit()) {
				$value = $item->get();
				foreach ($value->headers as $header) {
					header($header);
				}
				return $value->content;
			}
		}
		return null;
	}

	/**
	 * Page ID counter - always returns a unique ID PER Response
	 *
	 * @return number
	 */
	public function id_counter() {
		return $this->id_counter++;
	}

	/**
	 * Fetches the type to handle this content type
	 *
	 * @return \zesk\Response\Type
	 */
	private function _type($type) {
		if (isset($this->types[$type])) {
			return $this->types[$type];
		}
		if (!isset($type, self::$type_classes)) {
			return $this->_type(self::CONTENT_TYPE_RAW);
		}
		return $this->types[$type] = $this->application->factory(self::$type_classes[$type], $this);
	}

	/*====================================================================================================*\
	 * HTML-related
	 */
	/**
	 * Tracks HTML-related state for HTML pages
	 *
	 * @return HTMLResponse
	 */
	final public function html() {
		return $this->_type(self::content_type_html);
	}
	/**
	 * Get/set body attributes
	 *
	 * @param string|array $add
	 * @param string $value
	 * @return Response|string
	 */
	final public function body_attributes($add = null, $value = null) {
		return $this->html()->body_attributes($add, $value);
	}

	/**
	 * Add a class to the body tag
	 *
	 * @param string $add
	 * @return Response
	 */
	final public function body_add_class($add = null) {
		return $this->html()->body_add_class($add);
	}
	/**
	 * Get/set HTML attributes
	 *
	 * @param string $add
	 * @param string $value
	 * @return Response|string
	 */
	final public function html_attributes($add = null, $value = null) {
		return $this->html()->attributes($add, $value);
	}

	/**
	 * Get/set meta keywords
	 *
	 * @param string $content
	 * @return Response|string
	 */
	final public function meta_keywords($content = null) {
		return $this->html()->meta_keywords($content);
	}

	/**
	 * Get/set meta description text
	 *
	 * @param string $content
	 * @return Response|string
	 */
	final public function meta_description($content = null) {
		return $this->html()->meta_description($content);
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $path
	 *        	Path to css file
	 * @param array $options
	 *        	Optional options: media (defaults to all), type (defults to text/css), browser
	 *        	(may be ie,
	 *        	ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return void
	 */
	final public function css($path, $mixed = null, $options = null) {
		return $this->html()->css($path, $mixed, $options);
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @param null|string $set
	 * @return self|string
	 */
	final public function page_theme($set = false) {
		return $this->html()->page_theme($set);
	}

	/**
	 * Register a javascript to be put on the page
	 *
	 * @param string $path
	 *        	File path to serve for the javascript
	 * @param array $options
	 *        	Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *        	browsers),
	 *        	cdn (defaults to false)
	 * @return Response_Text_HTML
	 */
	final function javascript($path, $options = null) {
		return $this->html()->javascript($path, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param string $options
	 * @return Response_Text_HTML
	 */
	final function javascript_inline($script, $options = null) {
		return $this->html()->javascript_inline($script, $options);
	}

	/**
	 * Add to JavaScript script settings
	 *
	 * @param array $settings
	 */
	public final function javascript_settings(array $settings = null) {
		return $this->html()->javascript_settings($settings);
	}

	/**
	 * Require jQuery on the page, and optionally add a ready script
	 *
	 * @param string $add_ready_script
	 * @param string $weight
	 */
	final public function jquery($add_ready_script = null, $weight = null) {
		return $this->html()->jquery($add_ready_script, $weight);
	}

	/*====================================================================================================*\
	 * JSON-related
	 */

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
			$this->content_type = self::CONTENT_TYPE_JSON;
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
		return $this->content_type === self::CONTENT_TYPE_JSON;
	}

	/*====================================================================================================*\
	 * Raw-related
	 */
	/**
	 * Output a file
	 *
	 * @param unknown $file
	 * @throws Exception_File_NotFound
	 * @return string|\zesk\Response
	 */
	final function file($file = null) {
		return $this->raw()->file($file);
	}

	/**
	 * Download a file
	 *
	 * @param string $file
	 *        	Full path to file to download
	 * @param string $name
	 *        	File name given to browser to save the file
	 * @param string $type
	 *        	Content disposition type (attachment)
	 * @return \zesk\Response
	 */
	final public function download($file, $name = null, $type = null) {
		if ($name === null) {
			$name = basename($file);
		}
		$name = File::name_clean($name);
		if ($type === null) {
			$type = "attachment";
		}
		return $this->raw()
			->file($file)
			->header("Content-Disposition", "$type; filename=\"$name\"")
			->nocache();
	}
}
