<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use zesk\Response\HTML as HTMLResponse;
use zesk\Response\JSON;
use zesk\Response\Raw;
use zesk\Response\Redirect;
use zesk\Response\Text;
use zesk\Response\Type;

/**
 * Abstraction for web server responses to Request
 *
 * @see Request
 * @see \zesk\Response\HTML
 * @see \zesk\Response\JSON
 * @see \zesk\Response\Text
 * @see \zesk\Response\Redirect
 * @see \zesk\Response\Raw
 * @package zesk
 * @subpackage system
 */
class Response extends Hookable {
	/**
	 * Uniquely ID each Response created to avoid duplicates
	 *
	 * @var integer
	 */
	private static int $response_index = 0;

	/**
	 *
	 * @var array
	 */
	private static array $type_classes = [
		self::CONTENT_TYPE_HTML => HTMLResponse::class, self::CONTENT_TYPE_JSON => JSON::class,
		self::CONTENT_TYPE_PLAINTEXT => Text::class, self::CONTENT_TYPE_RAW => Raw::class,
		self::HANDLER_REDIRECT => Redirect::class,
	];

	/**
	 * @var string
	 */
	public const CONTENT_TYPE_JSON = 'application/json';

	/**
	 *
	 * @var string
	 */
	public const CONTENT_TYPE_HTML = 'text/html';

	/**
	 *
	 * @var string
	 */
	public const CONTENT_TYPE_PLAINTEXT = 'text/plain';

	/**
	 *
	 * @var string
	 */
	public const CONTENT_TYPE_RAW = 'application/octet-stream';

	/**
	 *
	 * @var string
	 */
	public const HANDLER_REDIRECT = 'redirect';

	/**
	 *
	 * @var integer
	 */
	public const CACHE_SCHEME = 1;

	/**
	 *
	 * @var integer
	 */
	public const CACHE_QUERY = 2;

	/**
	 *
	 * @var integer
	 */
	public const CACHE_PATH = 3;

	/**
	 * Ordered from most specific to least specific
	 *
	 * @var array
	 */
	private static array $cache_pattern = [
		self::CACHE_SCHEME => '{scheme}/{host}_{port}{path}/{query}',
		self::CACHE_QUERY => 'any/{host}_{port}{path}/{query}', self::CACHE_PATH => 'any/{host}_{port}{path}',
	];

	/**
	 * Cache responses to the request
	 *
	 * @var array
	 */
	private array $cache_settings = [];

	/**
	 *
	 * @var integer
	 */
	private int $id = 0;

	/**
	 * Request associated with this response
	 *
	 * @var Request
	 */
	public Request $request;

	/**
	 * Content to return (if small enough)
	 *
	 * @var ?string
	 */
	public ?string $content = '';

	/**
	 * File to return (for big stuff)
	 *
	 * @var string
	 */
	protected string $content_file = '';

	/**
	 * Status code
	 *
	 * @var int
	 */
	public int $status_code = Net_HTTP::STATUS_OK;

	/**
	 * Status message
	 *
	 * @var string
	 */
	public string $status_message = 'OK';

	/**
	 * Content-Type header
	 *
	 * @var string
	 */
	public string $content_type = self::CONTENT_TYPE_HTML;

	/**
	 * Optional Content-Type to determine output handler. If null, uses $this->content_type
	 *
	 * @var string
	 */
	public string $output_handler = '';

	/**
	 * Content-Type header
	 *
	 * @var string
	 */
	public string $charset = '';

	/**
	 *
	 * @var \zesk\Response\Type[]
	 */
	protected array $types = [];

	/**
	 * Headers.
	 * Key is always properly cased header. Values may be multi-array or string.
	 *
	 * @var array
	 */
	protected array $headers = [];

	/**
	 * Name/value data passed back to client if response type supports it.
	 *
	 * @var array
	 */
	protected array $response_data = [];

	/**
	 * ID counter for rendering things on the page which should have unique IDs
	 *
	 * @var integer
	 */
	private int $id_counter = 0;

	/**
	 * Flag to indicate that this object is currently rendering.
	 * Avoids infinite loops.
	 *
	 * @var boolean
	 */
	private bool $rendering = false;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Options::__sleep()
	 */
	public function __sleep() {
		return array_merge(parent::__sleep(), [
			'content', 'status_code', 'status_message', 'content_type', 'output_handler', 'charset', 'types', 'headers',
			'response_data',
		]);
	}

	public function __wakeup(): void {
		parent::__wakeup();
		$this->id = self::$response_index++;
	}

	/**
	 * Handle deprecated configuration
	 *
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		// Not sure when, let's say 2017-03
		$application->configuration->deprecated('Response', __CLASS__);
	}

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, Request $request, array $options = []): self {
		$result = $application->objects->factory(__CLASS__, $application, $request, $options);
		assert($result instanceof Response);
		return $result;
	}

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, Request $request, array $options = []) {
		$this->request = $request;
		parent::__construct($application, $options);
		$this->id = self::$response_index++;
		$this->inheritConfiguration();
		if (!$this->content_type) {
			$this->content_type($this->option('content_type', self::CONTENT_TYPE_HTML));
		}
	}

	/**
	 * Return Response id
	 *
	 * @return integer
	 */
	final public function id(): int {
		return $this->id;
	}

	/**
	 *
	 * @param int $error_code
	 * @param string $error_string
	 * @return $this
	 */
	public function status(int $error_code, string $error_string = null): self {
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
	 * @return self
	 */
	public function cookie(string $name, mixed $value = null, array $options = []) {
		$expire = avalue($options, 'expire', $this->option('cookie_expire'));
		if ($expire instanceof Timestamp) {
			$n_seconds = $expire->subtract(Timestamp::now($expire->timeZone()));
		} elseif (is_int($expire)) {
			$n_seconds = $expire;
		} else {
			$n_seconds = null;
		}
		$host = $this->request->host();
		$domain = $options['domain'] ?? $this->option('cookie_domain');
		if ($domain) {
			$domain = ltrim($domain, '.');
			if (!str_ends_with($host, $domain)) {
				$this->application->logger->warning('Unable to set cookie domain {cookie_domain} on host {host}', [
					'cookie_domain' => $domain, 'host' => $host,
				]);
				$domain = null;
			}
		}
		$secure = $options['secure'] ?? $this->optionBool('cookie_secure');
		$path = $options['path'] ?? $this->option('cookie_path', '/');
		if (!$domain) {
			$domain = Domain::domainFactory($this->application, $host)->computeCookieDomain();
		}
		$expire_time = $n_seconds ? time() + $n_seconds : null;
		if ($this->request->isBrowser()) {
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
	 * @return bool
	 */
	public function debug_redirect(): bool {
		return $this->debugRedirect();
	}

	/**
	 * @return bool
	 */
	public function debugRedirect(): bool {
		return $this->optionBool('debug_redirect');
	}

	/**
	 * Set up redirect debugging
	 *
	 * @param mixed $set
	 * @return self
	 */
	public function setDebugRedirect(bool $set): self {
		return $this->setOption('debug_redirect', $set);
	}

	/**
	 * Output a header
	 *
	 * @param string $string
	 *            Complete header line (e.g. "Location: /failed")
	 */
	private function _header(string $string): void {
		if ($this->cache_settings) {
			$this->cache_settings['headers'][] = $string;
		}
		header($string);
	}

	/**
	 *
	 * @throws Exception_Semantics
	 */
	private function response_headers(bool $skip_hooks = false): void {
		static $called = false;

		$do_hooks = !$skip_hooks;
		if ($do_hooks) {
			$this->callHook('headers_before');
		}
		if ($this->optionBool('skip_response_headers')) {
			return;
		}
		if ($called) {
			throw new Exception_Semantics('Response headers called twice! {previous}', [
				'previous' => $called,
			]);
		} else {
			$called = calling_function(2);
		}
		$file = $line = null;
		if (headers_sent($file, $line)) {
			throw new Exception_Semantics('Headers already sent on {file}:{line}', [
				'file' => $file, 'line' => $line,
			]);
		}
		if ($do_hooks) {
			$this->callHook('headers');
		}
		if (begins($this->content_type, 'text/')) {
			if (empty($this->charset)) {
				$this->charset = 'utf-8';
			}
			$content_type = $this->content_type . '; charset=' . $this->charset;
		} else {
			$content_type = $this->content_type;
		}
		if ($this->application->development() && $this->application->configuration->getPath([
			__CLASS__, 'json_to_html',
		])) {
			if (in_array($this->content_type, [
				self::CONTENT_TYPE_JSON,
			])) {
				$content_type = 'text/html; charset=' . $this->charset;
			}
		}
		$code = $this->status_code;
		if ($code !== Net_HTTP::STATUS_OK) {
			$message = $this->status_message;
			$message = $message ? $message : avalue(Net_HTTP::$status_text, $code, 'No error message');
			$this->_header('HTTP/1.0 ' . $this->status_code . ' ' . $message);
		}
		$this->_header('Content-Type: ' . $content_type);
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
	 * Is this content type text/html?
	 *
	 * @return bool
	 */
	final public function isHTML(): bool {
		return $this->content_type === self::CONTENT_TYPE_HTML;
	}

	/**
	 * Set the content type to text/html
	 *
	 * @return self
	 */
	final public function makeHTML(): self {
		$this->content_type = self::CONTENT_TYPE_HTML;
		return $this;
	}

	/**
	 * Set the content type to application/json
	 *
	 * @return self
	 */
	final public function makeJSON(): self {
		$this->content_type = self::CONTENT_TYPE_JSON;
		return $this;
	}

	/**
	 * Is this content type application/json?
	 *
	 * @return bool
	 */
	final public function isJSON(): bool {
		return $this->content_type === self::CONTENT_TYPE_JSON;
	}

	/**
	 * Do not cache this page
	 *
	 * @return \zesk\Response
	 */
	final public function nocache() {
		$this->cache_settings = [];
		$this->header('Cache-Control', 'no-cache, must-revalidate');
		$this->header('Pragma', 'no-cache');
		$this->header('Expires', '-1');
		return $this;
	}

	/**
	 * Getter/setter for content type of this response.
	 *
	 * @param ?string $set
	 * @return self|string
	 */
	final public function content_type(string $set = null): string|self {
		$this->application->deprecated(__METHOD__);
		if ($set !== null) {
			return $this->setContentType($set);
		}
		return $this->content_type;
	}

	/**
	 * Setter for content type of this response.
	 *
	 * @param string $set
	 * @return self
	 */
	final public function setContentType(string $set): self {
		$this->application->logger->debug('Set content type to {set} at {where}', [
			'set' => $set, 'where' => calling_function(),
		]);
		$this->content_type = $set;
		return $this;
	}

	/**
	 * Getter for content type of this response.
	 *
	 * @return string
	 */
	final public function contentType(): string {
		return $this->content_type;
	}

	/**
	 * Getter/setter for output handler for this response. Generally affects which
	 * Type handles output. If you want to force a handler, specify it as a parameter
	 * to force handler usage upon output. See \zesk\Response\Raw for pattern which uses this.
	 *
	 * @param ?string $set
	 * @return self|string
	 */
	final public function output_handler(string $set = null): self|string {
		$this->application->deprecated(__METHOD__);
		return $set ? $this->setOutputHandler($set) : $this->outputHandler();
	}

	/**
	 * Getter for output handler for this response. Generally affects which
	 * Type handles output. If you want to force a handler, specify it as a parameter
	 * to force handler usage upon output. See \zesk\Response\Raw for pattern which uses this.
	 *
	 * @return string
	 */
	final public function outputHandler(): string {
		return $this->output_handler;
	}

	/**
	 * Setter for output handler for this response. Generally affects which
	 * Type handles output. If you want to force a handler, specify it as a parameter
	 * to force handler usage upon output. See \zesk\Response\Raw for pattern which uses this.
	 *
	 * @param string $set
	 * @return \zesk\Response|string
	 */
	final public function setOutputHandler(string $set): self {
		$this->application->logger->debug('{method} set to {set} from {calling}', [
			'method' => __METHOD__, 'set' => $set, 'calling' => calling_function(2),
		]);
		$this->output_handler = $set;
		return $this;
	}

	/**
	 * Set a date header
	 *
	 * @param string $name Header to set (Expires, Date, Last-Modified, etc.)
	 * @param int|Timestamp $value
	 * @return $this
	 */
	final public function header_date(string $name, int|Timestamp $value): self {
		$this->application->deprecated(__METHOD__);
		return $this->headerDate($name, $value);
	}

	/**
	 * Set a date header
	 *
	 * @param string $name Header to set (Expires, Date, Last-Modified, etc.)
	 * @param int|Timestamp $value
	 * @return $this
	 */
	final public function headerDate(string $name, int|Timestamp $value): self {
		if ($value instanceof Timestamp) {
			$value = $value->unixTimestamp();
		}
		return $this->header($name, gmdate('D, d M Y H:i:s \G\M\T', $value));
	}

	/**
	 * Getter for header
	 *
	 * @param string $name Name of header to get
	 * @return string|array
	 * @throws Exception_Key
	 */
	final public function header(string $name): string|array {
		$lowName = strtolower($name);
		if ($lowName === 'content-type') {
			return $this->contentType();
		}
		$name = Net_HTTP::$response_headers[$lowName] ?? $name;
		if (array_key_exists($name, $this->headers)) {
			return $this->headers[$name];
		}

		throw new Exception_Key($name, 'No header found');
	}

	/**
	 * @return array
	 */
	final public function headers(): array {
		return $this->headers;
	}

	/**
	 * Setter for multiple headers
	 *
	 * @param array $values
	 * @return $this
	 */
	final public function setHeaders(array $values): self {
		foreach ($values as $k => $v) {
			$this->setHeader($k, $v);
		}
		return $this;
	}

	/**
	 * Setter for header
	 *
	 * @param string $name
	 *            Name of header to get/set
	 * @param string $value
	 *            Value of header to set
	 * @return mixed All headers if name is null, header value if name is set, $this if name and
	 *         value are set
	 */
	final public function setHeader(string $name, array|string $value): self {
		$lowName = strtolower($name);
		if ($lowName === 'content-type') {
			return $this->setContentType(toText($value));
		}
		$name = Net_HTTP::$response_headers[$lowName] ?? $name;
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Current output handler
	 *
	 * @return Type
	 * @throws Exception_Semantics
	 */
	private function _output_handler(): Type {
		$type = $this->output_handler;
		if (!$type) {
			$type = $this->content_type;
			if (!$type) {
				throw new Exception_Semantics('No content type set in {method}', [
					'method' => __METHOD__,
				]);
			}
		}
		return $this->_type($type);
	}

	/**
	 * Return response
	 *
	 * @return string
	 */
	final public function render(array $options = []): string {
		ob_start();
		$this->output($options);
		return ob_get_clean();
	}

	/**
	 * Echo response
	 *
	 * @return void
	 */
	public function output(array $options = []): void {
		if ($this->rendering) {
			return;
		}
		$this->rendering = true;
		$skip_hooks = toBool($options['skip_hooks'] ?? false);
		if (!$skip_hooks) {
			$this->application->callHook('response_output_before', $this);
			$this->callHook('output_before');
		}
		if (!($options['skip_headers'] ?? false)) {
			$this->response_headers($skip_hooks);
		}
		$this->_output_handler()->output($this->content);
		if (!$skip_hooks) {
			$this->application->callHook('response_output_after', $this);
			$this->callHook('output_after');
		}
		$this->rendering = false;
	}

	/**
	 * May call zesk\Response\Type::toJSON
	 *
	 * @return array
	 * @throws Exception_Semantics
	 */
	public function toJSON(): array {
		return $this->_output_handler()->toJSON() + $this->response_data;
	}

	/**
	 * Cache settings for this request
	 *
	 * "seconds" - For how many seconds
	 * "parts" - Url parts to match
	 *
	 * @param array $options
	 * @param boolean $append
	 * @return self
	 */
	public function setCache(array $options, bool $append = true): self {
		$this->cache_settings = $append ? $options + $this->cache_settings : $options;
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	public function setCacheForever(): self {
		return $this->setCache([
			'seconds' => 1576800000,
		]);
	}

	/**
	 * Cache for n seconds
	 *
	 * @param int $seconds
	 *            Number of seconds to cache this content
	 * @param int $level
	 *            What cache pattern to use to store this content
	 * @return \zesk\Response
	 */
	public function setCacheFor($seconds, int $level = self::CACHE_SCHEME): self {
		return $this->setCache([
			'seconds' => intval($seconds), 'level' => $level,
		]);
	}

	/**
	 * Convert URL into standard parts with defaults
	 *
	 * @param string $url
	 * @return array
	 */
	private static function cacheURLParts(string $url): array {
		try {
			$parts = toArray(URL::parse($url)) + [
				'scheme' => 'none',
			];
		} catch (Exception_Syntax $e) {
			/* URL should be valid therefore this never occurs */
			PHP::log($e);
			$parts = [];
		}
		$parts += [
			'port' => URL::protocolPort($parts['scheme']), 'scheme' => 'none', 'host' => '_host_',
			'query' => '_query_', 'path' => '_path_',
		];
		return $parts;
	}

	/**
	 * Is content type?
	 *
	 * @param string|array $mixed
	 * @return bool
	 */
	public function isContentType(string|array $mixed): bool {
		foreach (toList($mixed) as $type) {
			if (str_contains($this->content_type, $type)) {
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
	 * @return CacheItemInterface
	 */
	private static function fetchCacheID(CacheItemPoolInterface $pool, string $id): CacheItemInterface {
		$key = __CLASS__ . '::' . $id;

		try {
			return $pool->getItem($key);
		} catch (InvalidArgumentException $e) {
			/* Key should be valid therefore this never occurs */
			PHP::log($e->getMessage());
			return new CacheItem_NULL($key);
		}
	}

	/**
	 * Save this response's content, if the page requested to be cached
	 *
	 * @param CacheItemPoolInterface $pool
	 * @param string $url
	 * @return boolean
	 */
	public function cacheSave(CacheItemPoolInterface $pool, string $url): bool {
		if (count($this->cache_settings) === 0) {
			return false;
		}
		/* Typed */
		$level = toInteger($this->cache_settings['level'] ?? self::CACHE_SCHEME, self::CACHE_SCHEME);

		$pattern = self::$cache_pattern[$level] ?? self::$cache_pattern[self::CACHE_SCHEME];

		$parts = toArray($this->cache_settings['parts'] ?? []) + self::cacheURLParts($url);
		$item = self::fetchCacheID($pool, map($pattern, $parts));
		$response = $this->application->responseFactory($this->request);
		$response->output_handler(Response::CONTENT_TYPE_RAW);
		$response->content_type($this->content_type());

		$headers = toArray($this->cache_settings['headers'] ?? []);
		$response->setHeaders($headers + $this->headers());
		$response->content = $this->render([
			'skip_headers' => true,
		]);

		$seconds = toInteger($this->cache_settings['seconds'] ?? -1, -1);
		if ($seconds > 0) {
			$item->expiresAfter($seconds);
		}
		/* Multi type */
		$expires = $this->cache_settings['expires'] ?? null;
		if ($expires) {
			if ($expires instanceof \DateTimeInterface) {
				$item->expiresAt($expires);
			} elseif ($expires instanceof Timestamp) {
				$item->expiresAt($expires->datetime());
			} else {
				$this->application->logger->error('{method} expires is unhandled type: {type}', [
					'method' => __METHOD__, 'type' => type($expires),
				]);
			}
		}
		$this->application->cache->save($item->set($response));
		return true;
	}

	/**
	 * If content for URL is cached, invoke headers and return content.
	 *
	 * Returns null if cache item not found
	 *
	 * @param string $url
	 * @return ?Response
	 */
	public static function cached(CacheItemPoolInterface $pool, string $url): ?Response {
		$parts = self::cacheURLParts($url);
		foreach (self::$cache_pattern as $level => $id) {
			$id = map($id, $parts);
			$item = self::fetchCacheID($pool, $id);
			if ($item->isHit()) {
				return $item->get();
			}
		}
		return null;
	}

	/**
	 * Page ID counter - always returns a unique ID PER Response
	 *
	 * @return int
	 */
	public function id_counter(): int {
		return $this->id_counter++;
	}

	/**
	 * Fetches the type to handle this content type
	 *
	 * @param string $type String of content type to find/create.
	 * @return Type
	 * @throws Exception_Semantics When no content type is set
	 */
	private function _type(string $type): Type {
		if (isset($this->types[$type])) {
			return $this->types[$type];
		}
		if (!isset(self::$type_classes[$type])) {
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
	final public function html(): HTMLResponse {
		return $this->_type(self::CONTENT_TYPE_HTML);
	}

	/**
	 * Set page title
	 *
	 * @param string $set
	 * @return self
	 */
	public function setTitle(string $set): self {
		return $this->html()->setTitle($set);
	}

	/**
	 * Set page title
	 *
	 * @return string
	 */
	public function title(): string {
		return $this->html()->title();
	}

	/**
	 * Add a class to the body tag
	 *
	 * @param string $add
	 * @return Response
	 */
	final public function bodyAddClass(string $add): self {
		return $this->html()->bodyAddClass($add);
	}

	/**
	 * Get/set HTML attributes
	 *
	 * @param string $add
	 * @param string $value
	 * @return Response|string[]
	 */
	final public function htmlAttributes(): array {
		return $this->html()->attributes();
	}

	/**
	 * Get body attributes
	 *
	 * @return array
	 */
	final public function bodyAttributes(): array {
		return $this->html()->bodyAttributes();
	}

	/**
	 * Set body attributes
	 *
	 * @param array $attributes
	 * @return self
	 */
	final public function setBodyAttributes(array $attributes): self {
		return $this->html()->setBodyAttributes($attributes);
	}

	/**
	 * Set HTML attributes
	 *
	 * @param array $attributes
	 * @param bool $merge
	 * @return Response
	 */
	final public function setHTMLAttributes(array $attributes, bool $merge = false): Response {
		return $this->html()->setAttributes($attributes, $merge);
	}

	/**
	 * Set meta keywords
	 *
	 * @param string $content
	 * @return Response
	 */
	final public function setMetaKeywords(string $content): self {
		return $this->html()->setMetaKeywords($content);
	}

	final public function metaKeywords(): string {
		return $this->html()->metaKeywords();
	}

	/**
	 * Get meta description text
	 *
	 * @return string
	 */
	final public function metaDescription(): string {
		return $this->html()->metaDescription();
	}

	/**
	 * Set meta description text
	 *
	 * @param string $content
	 * @return Response|string
	 */
	final public function setMetaDescription(string $content): self {
		return $this->html()->setMetaDescription($content);
	}

	/**
	 * Add a css to the page
	 *
	 * @param string $path
	 *            Path to css file
	 * @param array $options
	 *            Optional options: media (defaults to all), type (defults to text/css), browser
	 *            (may be ie,
	 *            ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return void
	 */
	final public function css(string $path, array $options = []): self {
		return $this->html()->css($path, $options);
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
	 *            File path to serve for the javascript
	 * @param array $options
	 *            Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *            browsers),
	 *            cdn (defaults to false)
	 * @return Response
	 */
	final public function javascript($path, $options = null) {
		return $this->html()->javascript($path, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param string $options
	 * @return Response
	 */
	final public function javascript_inline($script, $options = null) {
		return $this->html()->inlineJavaScript($script, $options);
	}

	/**
	 * Add to JavaScript script settings
	 *
	 * @param array $settings
	 */
	final public function javascript_settings(array $settings = null) {
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
		if (func_num_args() !== 0) {
			zesk()->deprecated('{method} takes NO arguments', [
				'method' => __METHOD__,
			]);
		}
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
	 * Fetch JSON handler
	 *
	 * @param string $set
	 * @return Raw
	 */
	final public function raw() {
		return $this->_type(self::CONTENT_TYPE_RAW);
	}

	/**
	 * Output a file
	 *
	 * @param unknown $file
	 * @return string|\zesk\Response
	 * @throws Exception_File_NotFound
	 */
	final public function file($file = null) {
		return $this->raw()->file($file);
	}

	/**
	 * Download a file
	 *
	 * @param string $file
	 *            Full path to file to download
	 * @param string $name
	 *            File name given to browser to save the file
	 * @param string $type
	 *            Content disposition type (attachment)
	 * @return \zesk\Response
	 */
	final public function download($file, $name = null, $type = null) {
		return $this->raw()->download($file, $name, $type);
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
			$this->application->deprecated('[method} support for URL and message is deprecated 2018-01', [
				'method' => __METHOD__,
			]);
			return $this->redirect()->url($url, $message);
		}
		$this->output_handler(self::HANDLER_REDIRECT);
		return $this->_type(self::HANDLER_REDIRECT);
	}

	/**
	 * If ref is passed in by request, redirect to that location, otherwise, redirect to passed in
	 * URL
	 *
	 * @param string $url
	 * @param string $message
	 *            Already-localized message to display to user on redirected page
	 */
	public function redirect_default($url, $message = null): void {
		$ref = $this->request->get('ref', '');
		if (!empty($ref)) {
			$url = $ref;
		}
		$this->redirect()->url($url, $message);
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

	/**
	 * Getter/setter for skipping response headers (uses PHP header()) during output
	 *
	 * @param boolean $set
	 * @return boolean|self
	 */
	public function skip_response_headers($set = null) {
		return $set === null ? $this->optionBool('skip_response_headers') : $this->setOption('skip_response_headers', toBool($set));
	}

	/**
	 * Getter/setter for content
	 *
	 * @param string|NULL $content
	 * @return \zesk\Response|string
	 */
	public function content($content = null) {
		if ($content !== null) {
			$this->content = $content;
			return $this;
		}
		return $this->content;
	}

	/**
	 * Getter/setter for content
	 *
	 * @param string|NULL $content
	 * @return \zesk\Response|string
	 */
	public function setContent(string $content = null): self {
		$this->content = $content;
		return $this;
	}
}
