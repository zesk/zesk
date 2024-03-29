<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use DateTimeInterface;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

use zesk\CacheItem\CacheItemNULL;

use zesk\Exception\ClassNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\Exception\Redirect as RedirectException;
use zesk\Exception\SyntaxException;

use zesk\Response\HTML as HTMLResponse;
use zesk\Response\JSON as JSONResponse;
use zesk\Response\Raw;
use zesk\Response\Redirect as RedirectResponse;
use zesk\Response\Text;
use zesk\Response\Type;

/**
 * Abstraction for web server responses to Request
 *
 * @see Request
 * @see HTMLResponse
 * @see JSONResponse
 * @see Text
 * @see RedirectResponse
 * @see Raw
 * @package zesk
 * @subpackage system
 */
class Response extends Hookable {
	/**
	 *
	 */
	public const OPTION_RESOURCE_CACHE_EXPIRE_SECONDS = 'resource_path_route_expire';

	/**
	 *
	 */
	public const DEFAULT_RESOURCE_CACHE_EXPIRE_SECONDS = 600;

	/**
	 *
	 */
	public const OPTION_SKIP_HEADERS = 'skipHeaders';

	/**
	 * Cache scripts in HTML responses. Value is bool.
	 */
	public const OPTION_CACHE_SCRIPTS = 'cacheScripts';

	/**
	 * Cache links in HTML responses. Value is bool.
	 */
	public const OPTION_CACHE_LINKS = 'cacheLinks';

	/**
	 * Output the weights of scripts in HTML (debugging)
	 */
	public const OPTION_DEBUG_SCRIPT_WEIGHT = 'debugScriptWeight';

	/**
	 * Added to query strings of resource requests to force missed cache behavior.
	 */
	public const OPTION_NOCACHE_VARIABLE = 'noCacheVariable';

	/**
	 * Default variable used
	 */
	public const DEFAULT_NOCACHE_VARIABLE = '_r';

	/**
	 *
	 */
	public const OPTION_SKIP_HOOKS = 'skip_hooks';

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
		self::CONTENT_TYPE_HTML => HTMLResponse::class, self::CONTENT_TYPE_JSON => JSONResponse::class,
		self::CONTENT_TYPE_PLAINTEXT => Text::class, self::CONTENT_TYPE_RAW => Raw::class,
		self::HANDLER_REDIRECT => RedirectResponse::class,
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
	 * Ordered from most to least specific
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
	private int $id;

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
	public ?string $content = null;

	/**
	 * Status code
	 *
	 * @var int
	 */
	public int $status_code = HTTP::STATUS_OK;

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
	protected string $contentType = self::CONTENT_TYPE_HTML;

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
	 * @var Type[]
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

	public function __serialize(): array {
		return parent::__serialize() + [
			'content' => $this->content, 'status_code' => $this->status_code,
			'status_message' => $this->status_message, 'contentType' => $this->contentType,
			'output_handler' => $this->output_handler, 'charset' => $this->charset, 'types' => $this->types,
			'headers' => $this->headers, 'response_data' => $this->response_data,
		];
	}

	public function __unserialize(array $data): void {
		parent::__unserialize($data);
		$this->id = self::$response_index++;
		$this->content = $data['content'];
		$this->status_code = $data['status_code'];
		$this->status_message = $data['status_message'];
		$this->contentType = $data['contentType'];
		$this->output_handler = $data['output_handler'];
		$this->charset = $data['charset'];
		$this->types = $data['types'];
		$this->headers = $data['headers'];
		$this->response_data = $data['response_data'];
	}

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, Request $request, array $options = []): self {
		return new Response($application, $request, $options);
	}

	/**
	 *
	 */
	public const OPTION_CONTENT_TYPE = 'contentType';

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 * @param array $options
	 */
	public function __construct(Application $application, Request $request, array $options = []) {
		$this->request = $request;
		parent::__construct($application, $options);
		$this->id = self::$response_index++;
		$this->inheritConfiguration();
		if (!$this->contentType) {
			$this->setContentType($this->option(self::OPTION_CONTENT_TYPE, self::CONTENT_TYPE_HTML));
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
	 * @return int
	 */
	public function status(): int {
		return $this->status_code;
	}

	/**
	 *
	 * @return string
	 */
	public function statusMessage(): string {
		return $this->status_message;
	}

	/**
	 *
	 * @param string $error_string
	 * @return $this
	 */
	public function setStatusMessage(string $error_string): self {
		$this->status_message = $error_string;
		return $this;
	}

	/**
	 * If code is unknown, coverts to 500 automatically
	 * Also sets status message if not passed in; uses default.
	 *
	 * @param int $error_code
	 * @param ?string $error_string
	 * @return $this
	 */
	public function setStatus(int $error_code, string $error_string = null): self {
		$codes = HTTP::$status_text;
		$code = array_key_exists($error_code, $codes) ? $error_code : 500;
		$this->status_code = $code;
		if ($error_string === null) {
			$error_string = $codes[$code] ?? (array_key_exists($error_code, $codes) ? '' : "Unknown code $error_code");
		}
		$this->setStatusMessage($error_string);
		return $this;
	}

	/**
	 * These are not saved as part of cached headers, generally speaking
	 *
	 * @param string $name
	 * @param array|string|null $value
	 * @param array $options
	 * @return self
	 * @throws SemanticsException
	 */
	public function setCookie(string $name, array|string $value = null, array $options = []): self {
		$expire = $options['expire'] ?? $this->option('cookie_expire');
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
				$this->application->warning('Unable to set cookie domain {cookie_domain} on host {host}', [
					'cookie_domain' => $domain, 'host' => $host,
				]);
				$domain = null;
			}
		}
		$secure = $options['secure'] ?? $this->optionBool('cookie_secure');
		$path = $options['path'] ?? $this->option('cookie_path', '/');
		$expire_time = $n_seconds ? time() + $n_seconds : 0;
		if (!$this->request->isBrowser()) {
			throw new SemanticsException('Not a browser');
		}
		setcookie($name, '', 1, $path, ".$domain", $secure);
		if (!empty($value)) {
			setcookie($name, $value, $expire_time, $path, ".$domain", $secure);
		}
		return $this;
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

	public const HOOK_BEFORE_HEADERS = __CLASS__ . '::beforeHeaders';

	public const HOOK_AFTER_HEADERS = __CLASS__ . '::afterHeaders';

	/**
	 *
	 * @throws SemanticsException
	 */
	private function responseHeaders(bool $skip_hooks = false): void {
		static $called = false;

		$do_hooks = !$skip_hooks;
		if ($do_hooks) {
			$this->invokeHooks(self::HOOK_BEFORE_HEADERS, [$this]);
		}
		if ($this->optionBool(self::OPTION_SKIP_HEADERS)) {
			return;
		}
		if ($called) {
			throw new SemanticsException('Response headers called twice! {previous}', [
				'previous' => $called,
			]);
		} else {
			$called = Kernel::callingFunction(2);
		}
		$file = $line = null;
		if (headers_sent($file, $line)) {
			throw new SemanticsException('Headers already sent on {file}:{line}', [
				'file' => $file, 'line' => $line,
			]);
		}
		if ($do_hooks) {
			$this->invokeHooks(self::HOOK_AFTER_HEADERS, [$this]);
		}
		if (str_starts_with($this->contentType, 'text/')) {
			if ($this->charset === '') {
				$this->charset = 'utf-8';
			}
			$content_type = $this->contentType . '; charset=' . $this->charset;
		} else {
			$content_type = $this->contentType;
		}
		if ($this->application->development() && $this->application->configuration->getPath([
			__CLASS__, 'json_to_html',
		])) {
			if ($this->contentType == self::CONTENT_TYPE_JSON) {
				$content_type = 'text/html; charset=' . $this->charset;
			}
		}
		$code = $this->status_code;
		if ($code !== HTTP::STATUS_OK) {
			$message = $this->status_message;
			$message = $message ?: HTTP::$status_text[$code] ?? 'No error message';
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
		return $this->contentType === self::CONTENT_TYPE_HTML;
	}

	/**
	 * Set the content type to text/html
	 *
	 * @return self
	 */
	final public function makeHTML(): self {
		$this->contentType = self::CONTENT_TYPE_HTML;
		return $this;
	}

	/**
	 * Set the content type to application/json
	 *
	 * @return self
	 */
	final public function makeJSON(): self {
		$this->contentType = self::CONTENT_TYPE_JSON;
		return $this;
	}

	/**
	 * Is this content type application/json?
	 *
	 * @return bool
	 */
	final public function isJSON(): bool {
		return $this->contentType === self::CONTENT_TYPE_JSON;
	}

	/**
	 * Do not cache this page
	 *
	 * @return Response
	 */
	final public function noCache(): self {
		$this->cache_settings = [];
		$this->setHeader('Cache-Control', 'no-cache, must-revalidate');
		$this->setHeader('Pragma', 'no-cache');
		$this->setHeader('Expires', '-1');
		return $this;
	}

	/**
	 * Setter for content type of this response.
	 *
	 * @param string $set
	 * @return self
	 */
	final public function setContentType(string $set): self {
		$this->application->debug('Set content type to {set} at {where}', [
			'set' => $set, 'where' => Kernel::callingFunction(),
		]);
		$this->contentType = $set;
		return $this;
	}

	/**
	 * Getter for content type of this response.
	 *
	 * @return string
	 */
	final public function contentType(): string {
		return $this->contentType;
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
	 * @return Response
	 */
	final public function setOutputHandler(string $set): self {
		$this->application->debug('{method} set to {set} from {calling}', [
			'method' => __METHOD__, 'set' => $set, 'calling' => Kernel::callingFunction(2),
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
	final public function setHeaderDate(string $name, int|Timestamp $value): self {
		if ($value instanceof Timestamp) {
			$value = $value->unixTimestamp();
		}
		return $this->setHeader($name, gmdate('D, d M Y H:i:s \G\M\T', $value));
	}

	/**
	 * Getter for header
	 *
	 * @param string $name Name of header to get
	 * @return string|array
	 * @throws KeyNotFound
	 */
	final public function header(string $name): string|array {
		$lowName = strtolower($name);
		if ($lowName === 'content-type') {
			return $this->contentType();
		}
		$name = HTTP::$response_headers[$lowName] ?? $name;
		if (array_key_exists($name, $this->headers)) {
			return $this->headers[$name];
		}

		throw new KeyNotFound('No header {name} found', ['name' => $name]);
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
	 * @param array|string $value
	 *            Value of header to set
	 * @return mixed All headers if name is null, header value if name is set, $this if name and
	 *         value are set
	 */
	final public function setHeader(string $name, array|string $value): self {
		$lowName = strtolower($name);
		if ($lowName === 'content-type') {
			return $this->setContentType(Types::toText($value));
		}
		$name = HTTP::$response_headers[$lowName] ?? $name;
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Current output handler
	 *
	 * @return Type
	 * @throws SemanticsException
	 */
	private function _output_handler(): Type {
		$type = $this->output_handler;
		if (!$type) {
			$type = $this->contentType;
			if (!$type) {
				throw new SemanticsException('No content type set in {method}', [
					'method' => __METHOD__,
				]);
			}
		}
		return $this->_type($type);
	}

	/**
	 * Return response
	 *
	 * @param array $options
	 * @return string
	 * @throws SemanticsException
	 */
	final public function render(array $options = []): string {
		ob_start();
		$this->output($options);
		return ob_get_clean();
	}

	public const HOOK_OUTPUT_BEFORE = __CLASS__ . '::outputBefore';

	public const HOOK_OUTPUT_AFTER = __CLASS__ . '::outputBefore';

	/**
	 * Echo response
	 *
	 * @param array $options
	 * @return void
	 * @throws SemanticsException
	 */
	public function output(array $options = []): void {
		if ($this->rendering) {
			return;
		}
		$this->rendering = true;
		$skip_hooks = Types::toBool($options[self::OPTION_SKIP_HOOKS] ?? false);
		if (!$skip_hooks) {
			$this->invokeHooks(self::HOOK_OUTPUT_BEFORE, [$this]);
		}
		if (!($options[self::OPTION_SKIP_HEADERS] ?? $this->optionBool(self::OPTION_SKIP_HEADERS))) {
			$this->responseHeaders($skip_hooks);
		}
		$this->_output_handler()->output($this->content ?? '');
		if (!$skip_hooks) {
			$this->invokeHooks(self::HOOK_OUTPUT_AFTER, [$this]);
		}
		$this->rendering = false;
	}

	/**
	 * May call zesk\Response\Type::toJSON
	 *
	 * @return array
	 * @throws SemanticsException
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
	 * @return Response
	 */
	public function setCacheFor(int $seconds, int $level = self::CACHE_SCHEME): self {
		return $this->setCache([
			'seconds' => $seconds, 'level' => $level,
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
			$parts = Types::toArray(URL::parse($url)) + [
				'scheme' => 'none',
			];
		} catch (SyntaxException $e) {
			/* URL should be valid therefore this never occurs */
			PHP::log($e);
			$parts = [];
		}
		$parts += [
			'port' => URL::protocolPort($parts['scheme']), 'scheme' => 'none', 'host' => '_host_', 'query' => '_query_',
			'path' => '_path_',
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
		foreach (Types::toList($mixed) as $type) {
			if (str_contains($this->contentType, $type)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Retrieve a cached response
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
			return new CacheItemNULL($key);
		}
	}

	/**
	 * Save this response's content, if the page requested to be cached
	 *
	 * @param CacheItemPoolInterface $pool
	 * @param string $url
	 * @return boolean
	 * @throws SemanticsException
	 */
	public function cacheSave(CacheItemPoolInterface $pool, string $url): bool {
		if (count($this->cache_settings) === 0) {
			return false;
		}
		/* Typed */
		$level = Types::toInteger($this->cache_settings['level'] ?? self::CACHE_SCHEME, self::CACHE_SCHEME);

		$pattern = self::$cache_pattern[$level] ?? self::$cache_pattern[self::CACHE_SCHEME];

		$parts = Types::toArray($this->cache_settings['parts'] ?? []) + self::cacheURLParts($url);
		$item = self::fetchCacheID($pool, ArrayTools::map($pattern, $parts));
		$response = $this->application->responseFactory($this->request);
		$response->setOutputHandler(Response::CONTENT_TYPE_RAW);
		$response->setContentType($this->contentType());

		$headers = Types::toArray($this->cache_settings['headers'] ?? []);
		$response->setHeaders($headers + $this->headers());
		$response->content = $this->render([
			Response::OPTION_SKIP_HEADERS => true,
		]);

		$seconds = Types::toInteger($this->cache_settings['seconds'] ?? -1, -1);
		if ($seconds > 0) {
			$item->expiresAfter($seconds);
		}
		/* Multi type */
		$expires = $this->cache_settings['expires'] ?? null;
		if ($expires) {
			if ($expires instanceof DateTimeInterface) {
				$item->expiresAt($expires);
			} elseif ($expires instanceof Timestamp) {
				$item->expiresAt($expires->datetime());
			} else {
				$this->application->error('{method} expires is unhandled type: {type}', [
					'method' => __METHOD__, 'type' => Types::type($expires),
				]);
			}
		}
		$this->application->cacheItemPool()->save($item->set($response));
		return true;
	}

	/**
	 * If content for URL is cached, invoke headers and return content.
	 *
	 * Returns null if cache item not found
	 *
	 * @param CacheItemPoolInterface $pool
	 * @param string $url
	 * @return ?Response
	 */
	public static function cached(CacheItemPoolInterface $pool, string $url): ?Response {
		$parts = self::cacheURLParts($url);
		foreach (self::$cache_pattern as $id) {
			$id = ArrayTools::map($id, $parts);
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
	 */
	private function _type(string $type): Type {
		if (isset($this->types[$type])) {
			return $this->types[$type];
		}
		if (!array_key_exists($type, self::$type_classes)) {
			return $this->_type(self::CONTENT_TYPE_RAW);
		}

		try {
			$type = $this->types[$type] = $this->application->factory(self::$type_classes[$type], $this);
			assert($type instanceof Type);
			return $type;
		} catch (ClassNotFound) {
			return $this->types[self::CONTENT_TYPE_RAW] = new Raw($this);
		}
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
		$result = $this->_type(self::CONTENT_TYPE_HTML);
		assert($result instanceof HTMLResponse);
		return $result;
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
	 * @return array
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

	/**
	 * @return string
	 * @throws KeyNotFound
	 */
	final public function metaKeywords(): string {
		return $this->html()->metaKeywords();
	}

	/**
	 * Get meta description text
	 *
	 * @return string
	 * @throws KeyNotFound
	 */
	final public function metaDescription(): string {
		return $this->html()->metaDescription();
	}

	/**
	 * Set meta description text
	 *
	 * @param string $content
	 * @return self
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
	 *            Optional options: media (defaults to all), type (defaults to text/css), browser
	 *            (may be ie,
	 *            ie6, ie7), and cdn (boolean to prefix with cdn path)
	 * @return self
	 * @throws SemanticsException
	 */
	final public function css(string $path, array $options = []): self {
		return $this->html()->css($path, $options);
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @return string
	 */
	final public function pageTheme(): string {
		return $this->html()->pageTheme();
	}

	/**
	 * Set the page theme to use to render the final HTML output
	 *
	 * @param string $set
	 * @return self
	 */
	final public function setPageTheme(string $set): self {
		$this->html()->setPageTheme($set);
		return $this;
	}

	/**
	 * Register a javascript to be put on the page
	 *
	 * @param string|array $path
	 *            File path to serve for the javascript
	 * @param array $options
	 *            Optional settings: type (defaults to text/javascript), browser (defaults to all
	 *            browsers),
	 *            cdn (defaults to false)
	 * @return Response
	 * @throws SemanticsException
	 */
	final public function javascript(string|array $path, array $options = []): Response {
		return $this->html()->javascript($path, $options);
	}

	/**
	 * Include JavaScript to be included inline in the page
	 *
	 * @param string $script
	 * @param array $options
	 * @return Response
	 * @throws SemanticsException
	 */
	final public function inlineJavaScript(string $script, array $options = []): self {
		return $this->html()->inlineJavaScript($script, $options);
	}

	/*====================================================================================================*\
	 * JSON-related
	 */

	/**
	 * Fetch JSON handler
	 *
	 * @return JSONResponse
	 */
	final public function json(): JSONResponse {
		$result = $this->_type(self::CONTENT_TYPE_JSON);
		assert($result instanceof JSONResponse);
		return $result;
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
	 * @param array $data
	 * @param bool $add
	 * @return Response
	 */
	final public function setResponseData(array $data, bool $add = true): self {
		$this->response_data = $add ? $data + $this->response_data : $data;
		return $this;
	}

	/**
	 * @return array
	 */
	final public function responseData(): array {
		return $this->response_data;
	}

	/*====================================================================================================*\
	 * Raw-related
	 */

	/**
	 * Fetch JSON handler
	 *
	 * @return Raw
	 */
	final public function raw(): Raw {
		$result = $this->_type(self::CONTENT_TYPE_RAW);
		assert($result instanceof Raw);
		return $result;
	}

	/**
	 * Output a file
	 *
	 * @param string|null $file
	 * @return string|$this
	 * @throws FileNotFound
	 * @throws SemanticsException
	 */
	final public function file(string $file = null): string|self {
		return $file ? $this->setRawFile($file) : $this->raw()->file();
	}

	/**
	 * Output a file
	 *
	 * @param string $file
	 * @return $this
	 * @throws FileNotFound
	 */
	final public function setRawFile(string $file): self {
		return $this->raw()->setFile($file);
	}

	/**
	 * Download a file
	 *
	 * @param string $file
	 *            Full path to file to download
	 * @param string|null $name
	 *            File name given to browser to save the file
	 * @param string|null $type
	 *            Content disposition type (attachment)
	 * @return Response
	 * @throws FileNotFound
	 */
	final public function download(string $file, string $name = null, string $type = null): Response {
		return $this->raw()->download($file, $name, $type);
	}

	/*====================================================================================================*\
	 * Redirect-related
	 */

	/**
	 * Fetch Redirect handler
	 *
	 * @return RedirectResponse
	 */
	final public function redirect(): RedirectResponse {
		$result = $this->setOutputHandler(self::HANDLER_REDIRECT)->_type(self::HANDLER_REDIRECT);
		assert($result instanceof RedirectResponse);
		return $result;
	}

	/**
	 * If ref is passed in by request, redirect to that location, otherwise, redirect to passed in
	 * URL.
	 *
	 * @param string $url
	 * @param string|null $message Already-localized message to display to user on redirected page
	 * @return void
	 * @throws RedirectException
	 */
	public function redirectDefault(string $url, string $message = null): void {
		$ref = $this->request->get('ref', '');
		if (!empty($ref)) {
			$url = $ref;
		}
		$this->redirect()->url($url, $message);
	}

	/**
	 *
	 * @return self
	 */
	public function redirectMessageClear(): self {
		$this->redirect()->messageClear();
		return $this;
	}

	/**
	 *
	 * @param string $message
	 * @param array $attributes
	 * @return Response
	 */
	public function addRedirectMessage(string $message, array $attributes = []): Response {
		return $this->redirect()->addMessage($message, $attributes);
	}

	/**
	 *
	 * @return array
	 */
	public function redirectMessages(): array {
		return $this->redirect()->messages();
	}

	/**
	 * Getter/setter for skipping response headers (uses PHP header()) during output
	 *
	 * @return bool
	 */
	public function skipResponseHeaders(): bool {
		return $this->optionBool(self::OPTION_SKIP_HEADERS);
	}

	/**
	 * Getter/setter for skipping response headers (uses PHP header()) during output
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setSkipResponseHeaders(bool $set): self {
		return $this->setOption(self::OPTION_SKIP_HEADERS, $set);
	}

	/**
	 * Getter for content
	 *
	 * @return ?string
	 */
	public function content(): ?string {
		return $this->content;
	}

	/**
	 * Setter for content
	 *
	 * @param string|NULL $content
	 * @return self
	 */
	public function setContent(string $content = null): self {
		$this->content = $content;
		return $this;
	}

	/**
	 * Caching resources with scripts, cache for this long
	 *
	 * @return int
	 */
	public function resourceExpireSeconds(): int {
		return $this->optionInt(self::OPTION_RESOURCE_CACHE_EXPIRE_SECONDS, self::DEFAULT_RESOURCE_CACHE_EXPIRE_SECONDS);
	}
}
