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
use zesk\Response\Redirect;
use zesk\Logger\Handler;

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
	 * @var string
	 */
	const HANDLER_REDIRECT = "redirect";
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
	public $content_type = self::CONTENT_TYPE_HTML;

	/**
	 * Optional Content-Type to determine output handler. If null, uses $this->content_type
	 *
	 * @var string
	 */
	public $output_handler = null;

	/**
	 * Content-Type header
	 *
	 * @var string
	 */
	public $charset = null;

	/**
	 *
	 * @var array
	 */
	static $type_classes = array(
		self::CONTENT_TYPE_HTML => HTMLResponse::class,
		self::CONTENT_TYPE_JSON => JSON::class,
		self::CONTENT_TYPE_PLAINTEXT => Text::class,
		self::CONTENT_TYPE_RAW => Raw::class,
		self::HANDLER_REDIRECT => Redirect::class
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
	 * Handle deprecated configuration
	 *
	 * @param Kernel $kernel
	 */
	public static function hooks(Application $application) {
		// Not sure when, let's say 2017-03
		$application->configuration->deprecated("Response", __CLASS__);
	}
	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return \zesk\stdClass|\zesk\Response
	 */
	public static function factory(Application $application, Request $request, array $options = array()) {
		return $application->objects->factory(__CLASS__, $application, $request, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $options
	 */
	function __construct(Application $application, Request $request, array $options = array()) {
		$this->request = $request;
		parent::__construct($application, $options);
		$this->inherit_global_options();
		if (!$this->content_type) {
			$this->content_type($this->option("content_type", self::CONTENT_TYPE_HTML));
		}
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
	 * @throws Exception_Semantics
	 */
	private function response_headers() {
		static $called = false;

		$this->call_hook('headers_before');
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
			$this->content_type = self::CONTENT_TYPE_HTML;
			return $this;
		}
		return $this->content_type === self::CONTENT_TYPE_HTML;
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
	 * Getter/setter for content type of this response.
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
	 * Getter/setter for content type of this response. Generally affects which
	 * Type handles output. If you want to force a handler, specify it as the 2nd parameter
	 * to force handler usage upon output. See \zesk\Response\Raw for pattern which uses this.
	 *
	 * @param string $set
	 * @return \zesk\Response|string
	 */
	final public function output_handler($set = null) {
		if ($set !== null) {
			$this->output_handler = $set;
			return $this;
		}
		return $this->output_handler;
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
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->header($k, $v);
			}
			return $this;
		}
		$lowname = strtolower($name);
		if ($lowname === "content-type") {
			if ($value === null) {
				return $this->content_type();
			}
			$this->content_type($value);
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
	 * Current output handler
	 *
	 * @throws Exception_Semantics
	 * @return \zesk\Response\Type
	 */
	private function _output_handler() {
		$type = $this->output_handler;
		if (!$type) {
			$type = $this->content_type;
			if (!$type) {
				throw new Exception_Semantics("No content type set in {method}", array(
					"method" => __METHOD__
				));
			}
		}
		return $this->_type($type);
	}

	/**
	 * Return response
	 *
	 * @return string
	 */
	final function render(array $options = array()) {
		ob_start();
		$this->output_content($options);
		return ob_get_clean();
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
		$this->_output_handler()->output($this->content);
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
	public function cache_save(CacheItemPoolInterface $pool, $url) {
		if ($this->cache_settings === null) {
			return false;
		}

		$parts = self::_cache_parts($url);
		$level = self::CACHE_SCHEME;
		$seconds = $expires = null;
		$headers = array();
		extract($this->cache_settings, EXTR_IF_EXISTS);
		$pattern = avalue(self::$cache_pattern, $level, self::$cache_pattern[self::CACHE_SCHEME]);

		$item = self::fetch_cache_id($pool, map($pattern, $parts));
		$response = new Response($this->application);
		$response->output_handler(Response::CONTENT_TYPE_RAW);
		$response->content_type($this->content_type());
		$response->header($this->header());
		$response->content = $this->content;

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
	 * @return NULL|Response
	 */
	public static function cached(CacheItemPoolInterface $pool, $url) {
		$parts = self::_cache_parts($url);
		foreach (self::$cache_pattern as $level => $id) {
			$id = map($id, $parts);
			$item = self::fetch_cache_id($pool, $id);
			if ($item->isHit()) {
				return $item->get();
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
	 * @param string $type String of content type to find/create.
	 * @throws Exception_Semantics When no content type is set
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
		return $this->_type(self::CONTENT_TYPE_HTML);
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
	 * @return Response
	 */
	final function javascript($path, $options = null) {
		return $this->html()->javascript($path, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param string $options
	 * @return Response
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
	 * Fetch JSON handler
	 *
	 * @param string $set
	 * @return JSON
	 */
	final public function json() {
		return $this->_type(self::CONTENT_TYPE_JSON);
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

	/*====================================================================================================*\
	 * Redirect-related
	 */
	/**
	 * Fetch Redirect handler
	 *
	 * @param string $url
	 * @param string $message
	 * @return Redirect
	 */
	final public function redirect($url = null, $message = null) {
		if ($url) {
			$this->application->deprecated("[method} support for URL and message is deprecated 2018-01", array(
				"method" => $method
			));
			return $this->redirect()->url($url, $message);
		}
		$this->output_handler(self::HANDLER_REDIRECT);
		return $this->_type(self::HANDLER_REDIRECT);
	}

	/**
	 *
	 * @return \zesk\Response
	 */
	public function redirect_message_clear() {
		$this->redirect()->message_clear();
		return $this;
	}

	/**
	 *
	 * @param string $message
	 * @return \zesk\Response
	 */
	public function redirect_message($message = null) {
		return $this->redirect()->message($message);
	}
}
