<?php
declare(strict_types=1);

/**
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	use GetTyped;

	/**
	 * fromSettings
	 */
	public const OPTION_METHOD = 'method';

	public const OPTION_HEADERS = 'headers';

	public const OPTION_URI = 'uri';

	public const OPTION_COOKIES = 'cookies';

	public const OPTION_REQUEST_DATA = 'requestData';

	public const OPTION_FILES = 'files';

	public const OPTION_DATA_FILE = 'dataFile';

	public const OPTION_IP = 'ip';

	public const OPTION_REMOTE_IP = 'remoteIP';

	public const OPTION_SERVER_IP = 'serverIP';

	public const OPTION_DATA = 'data';

	public const OPTION_URL = 'url';

	public const OPTION_USER_AGENT = 'userAgent';

	/**
	 * NOT fromSettings
	 */
	public const OPTION_URL_PARTS = 'urlParts';

	/**
	 * Default URI
	 */
	public const DEFAULT_URI = '/';

	public const DEFAULT_IP = '0.0.0.0';

	/**
	 * Default name of file to read for POST ar PUT content
	 *
	 * @var string
	 */
	public const DATA_FILE_DEFAULT = 'php://input';

	/**
	 * Method of request GET, POST, PUT, POST, DELETE etc.
	 *
	 * @var string
	 */
	protected string $method = HTTP::METHOD_GET;

	/**
	 * Requested URI
	 *
	 * @var string
	 */
	protected string $uri = '/';

	/**
	 * Request headers (reconstructed)
	 */
	protected array $headers = [];

	/**
	 * Request headers parse cache
	 */
	private array $headers_parsed = [];

	/**
	 * Cookies
	 */
	protected array $cookies = [];

	/**
	 * Processed data converted to internal structure
	 *
	 * @var ?array
	 */
	protected ?array $data;

	/**
	 * Where to retrieve the data from
	 *
	 * @var string
	 */
	protected string $dataFile = self::DATA_FILE_DEFAULT;

	/**
	 * Parsed request variables (see $_REQUEST)
	 *
	 * @var array
	 */
	protected array $requestData = [];

	/**
	 * Parsed file uploads (see $_FILES)
	 *
	 * @var array
	 */
	protected array $files = [];

	/**
	 * Complete URL
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 *
	 * @var array
	 */
	protected array $urlParts = [
		'host' => null, 'scheme' => null, 'path' => null,
	];

	/**
	 *
	 * @var ?UserAgent
	 */
	protected ?UserAgent $userAgent = null;

	/**
	 * Way to mock IP addresses if needed.
	 * Defaults to $_SERVER variables based on load balancers or reverse proxies.
	 *
	 * @var string
	 */
	protected string $ip = self::DEFAULT_IP;

	/**
	 * Server IP address
	 *
	 * Defaults to $_SERVER['SERVER_ADDR']
	 *
	 * @var string
	 */
	protected string $serverIP = self::DEFAULT_IP;

	/**
	 * Remote IP address
	 *
	 * Defaults to $_SERVER['REMOTE_ADDR']
	 *
	 * @var string
	 */
	protected string $remoteIP = self::DEFAULT_IP;

	/**
	 *
	 * @var string
	 */
	protected string $init = 'class';

	/**
	 *
	 * @param Application $application
	 * @param string|array|self|null $settings If NULL, uses PHP globals to initialize
	 * @return self
	 * @throws Exception_File_NotFound
	 * @throws Exception_Parameter|Exception_Parse
	 */
	public static function factory(Application $application, string|array|self $settings = null): self {
		$request = new self($application);
		return $request->initializeFromSettings($settings);
	}

	/**
	 * Create a new Request object
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		parent::__construct($application);
		$this->userAgent = null;
		$this->inheritConfiguration();
		$this->init = 'uninitialized';
	}

	public function __sleep() {
		return array_merge(parent::__sleep(), ['method', 'uri', 'headers', 'headers_parsed', 'cookies', 'data_raw', 'data', 'dataFile', 'requestData', 'files', 'url', 'urlParts', 'userAgent', 'ip', 'serverIP', 'remoteIP', 'init']);
	}

	/**
	 * Create a Request from PHP Superglobals $_SERVER, $_COOKIE, $_GET, $_REQUEST
	 *
	 * Supports PUT, POST, GET and POST with application/json Content-Type parsing of JSON
	 *
	 * @return self
	 * @throws Exception_Parameter|Exception_Parse
	 */
	public function initializeFromGlobals(): self {
		$this->dataFile = self::DATA_FILE_DEFAULT;

		$this->ip = $this->_findRemoteKey($_SERVER);
		$this->remoteIP = $_SERVER['REMOTE_ADDR'] ?? self::DEFAULT_IP;
		$this->serverIP = $_SERVER['SERVER_ADDR'] ?? self::DEFAULT_IP;

		$this->setMethod($_SERVER ['REQUEST_METHOD'] ?? HTTP::METHOD_GET);
		$this->uri = $_SERVER['REQUEST_URI'] ?? self::DEFAULT_URI;
		$this->headers = self::httpHeadersFromSERVER($_SERVER);
		$this->cookies = $_COOKIE;
		$this->url = $this->urlFromSERVER($_SERVER);
		$this->files = is_array($_FILES) ? $_FILES : [];

		$this->urlParts = [];

		$this->requestData = $this->defaultRequestData();

		$this->init = 'globals';

		$this->_initializeData();

		$this->callHook([
			'initialize', 'initializeFromGlobals',
		]);

		return $this;
	}

	/**
	 * Copy from another request
	 *
	 * @param Request $request
	 * @return self
	 */
	public function initializeFromRequest(Request $request): self {
		$this->method = $request->method;
		$this->uri = $request->uri;
		$this->headers = $request->headers;
		$this->cookies = $request->cookies;
		$this->requestData = $request->requestData;
		$this->files = $request->files;
		$this->url = $request->url;
		$this->urlParts = $request->urlParts;
		$this->data = $request->data; // Note: Loads data once if necessary
		$this->dataFile = $request->dataFile;
		$this->ip = $request->ip;
		$this->remoteIP = $request->remoteIP;
		$this->serverIP = $request->serverIP;

		$this->init = 'request';

		$this->callHook([
			'initialize', 'initializeFromRequest',
		]);

		return $this;
	}

	/**
	 * Initialze the object from settings (for mock objects)
	 *
	 * @param string|array|Request $settings
	 * @return self
	 * @throws Exception_File_NotFound
	 * @throws Exception_Parameter|Exception_Parse
	 */
	public function initializeFromSettings(string|array|Request $settings): self {
		if (is_string($settings)) {
			$settings = [
				'url' => $settings,
			];
		} elseif ($settings instanceof Request) {
			return $this->initializeFromRequest($settings);
		}
		$this->setMethod($settings[self::OPTION_METHOD] ?? 'GET');
		$this->uri = $settings[self::OPTION_URI] ?? '';
		if (is_array($settings[self::OPTION_HEADERS] ?? null)) {
			foreach ($settings[self::OPTION_HEADERS] as $k => $v) {
				$this->setHeader($k, $v);
			}
		}
		$this->userAgent = $settings[self::OPTION_USER_AGENT] ?? null;
		$this->cookies = toArray($settings[self::OPTION_COOKIES] ?? []);
		$this->requestData = toArray($settings[self::OPTION_REQUEST_DATA] ?? []);
		$this->files = toArray($settings[self::OPTION_FILES] ?? []);
		$this->url = $settings[self::OPTION_URL] ?? '';
		$this->urlParts = [];
		if (!$this->uri) {
			$this->uri = $this->query() ? URL::queryFormat($this->path(), $this->query()) : $this->path();
		}
		$data_file = $settings[self::OPTION_DATA_FILE] ?? null;
		if ($data_file) {
			if (!is_file($data_file)) {
				throw new Exception_File_NotFound($data_file, 'Passed {filename} as settings to new Request {settings}', [
					'settings' => $settings,
				]);
			}
			$this->dataFile = $data_file;
			$this->data = $settings[self::OPTION_DATA] ?? null;
		} else {
			$this->data = $settings[self::OPTION_DATA] ?? [];
			$this->dataFile = '';
		}
		$this->ip = $settings[self::OPTION_IP] ?? self::DEFAULT_IP;
		$this->remoteIP = $settings[self::OPTION_REMOTE_IP] ?? self::DEFAULT_IP;
		$this->serverIP = $settings[self::OPTION_SERVER_IP] ?? self::DEFAULT_IP;

		$this->init = 'settings';

		$this->_initializeData();
		$this->_validURLParts();

		$this->callHook([
			'initialize', 'initializeFromSettings',
		]);

		return $this;
	}

	/**
	 * Is this request secure?
	 *
	 * @return boolean
	 */
	public function isSecure(): bool {
		$this->_validURLParts();
		return $this->urlParts['scheme'] === 'https';
	}

	/**
	 * Retrieve the content type of the request
	 *
	 * @return string
	 * @throws Exception_Key
	 */
	public function contentType(): string {
		$type = explode(';', $this->header(HTTP::REQUEST_CONTENT_TYPE));
		return strtolower(array_shift($type));
	}

	/**
	 * Parse the "Accept:" header and return in priority order
	 *
	 * @return array
	 */
	public function parseAccept(): array {
		$name = HTTP::REQUEST_ACCEPT;
		$result = $this->_parsedHeader($name);
		if ($result) {
			return $result;
		}

		try {
			$accept = $this->header($name);
			$result = $this->_parseAccept($accept);
			$this->_setParsedHeader($name, $result);
			return $result;
		} catch (Exception_Key) {
			return [
				'*/*' => [
					'q' => 1, 'pattern' => '#[^/]+/[^/]+#',
				],
			];
		}
	}

	/**
	 * @param string $accept
	 * @return array
	 */
	private function _parseAccept(string $accept): array {
		$items = explode(',', preg_replace('/\s+/', '', $accept));
		foreach ($items as $item_index => $item) {
			$item_parts = explode(';', $item);
			$type = $subtype = '*';
			$attr = [
				'weight' => 1,
			];

			$attr = [];
			foreach ($item_parts as $item_part) {
				if (str_contains($item_part, '/')) {
					[$type, $subtype] = explode('/', $item_part, 2);
					if (isset($attr['weight'])) {
						continue;
					}
					if ($type === '*') {
						$weight = 0;
					} elseif ($subtype === '*') {
						$weight = 0;
					} else {
						$weight = 1 + ($item_index * 0.01);
					}
					$attr['weight'] = $weight;
				} elseif (strpos($item_part, '=')) {
					[$name, $value] = explode('=', $item_part, 2);
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
			$attr['pattern'] = '#' . strtr($key, [
				'*' => '[^/]+', '+' => '\\+',
			]) . '#';
			$result[$key] = $attr;
		}
		uasort($result, fn ($a, $b) => zesk_sort_weight_array($a, $b));
		return $result;
	}

	/**
	 * Helper to determine the best choice for response given the Accept: header
	 *
	 * @param string|array $available_responses
	 * @return string|null
	 */
	public function acceptPriority(string|array $available_responses): ?string {
		$result = [];
		$accept = $this->parseAccept();
		foreach (toList($available_responses) as $mime_type) {
			if (isset($accept[$mime_type])) {
				$result[$mime_type] = $accept[$mime_type];
				continue;
			}
			foreach ($accept as $attr) {
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
			uasort($result, 'zesk_sort_weight_array_reverse');
		}
		return first(array_keys($result));
	}

	/**
	 * Retrieve raw POST or PUT data from this request
	 *
	 * @return void
	 * @throws Exception_Parse
	 */
	private function _initializeData(): void {
		$this->data = [];
		if (!$this->dataFile) {
			return;
		}
		$rawData = file_get_contents($this->dataFile);
		if ($rawData === '') {
			return;
		}

		try {
			$content_type = StringTools::left($this->contentType(), ';');
			switch ($content_type) {
				case 'application/json':
					$this->data = strlen($rawData) > 0 ? JSON::decode($rawData) : [];
					break;
				case 'application/x-www-form-urlencoded':
					parse_str($rawData, $this->data);
					break;
				case 'multipart/form-data':
					/* Why NOT rawData? TODO KMD 2023 */
					$this->data = $_REQUEST;
					break;
				default:
					break;
			}
		} catch (Exception_Key) {
			/* No content type, set to empty */
		}
	}

	/**
	 * Retrieve raw POST or PUT data from this request
	 *
	 * @return array
	 */
	public function data(): array {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function rawData(): string {
		return $this->rawData;
	}

	/**
	 * @return array
	 */
	public function headers(): array {
		return ArrayTools::keysMap($this->headers, HTTP::$request_headers);
	}

	/**
	 * Retrieve a header
	 *
	 * @param string $key
	 * @return array|string
	 * @throws Exception_Key
	 */
	public function header(string $key): string|array {
		$low_key = strtolower($key);
		if (array_key_exists($low_key, $this->headers)) {
			return $this->headers[$low_key];
		}

		throw new Exception_Key($key);
	}

	/**
	 * Retrieve a header
	 *
	 * @param string $key
	 * @param string|array $value
	 * @return self
	 */
	public function setHeader(string $key, string|array $value): self {
		$this->headers[strtolower($key)] = $value;
		unset($this->headers_parsed[strtolower($key)]);
		return $this;
	}

	/**
	 * Getter/setter for parsed header values
	 *
	 * @param string $key
	 * @return mixed
	 */
	private function _parsedHeader(string $key): mixed {
		$key = strtolower($key);
		return $this->headers_parsed[$key] ?? null;
	}

	/**
	 * Getter/setter for parsed header values
	 *
	 * @param string $key
	 * @param string|array $value Optional value to
	 * @return void
	 */
	private function _setParsedHeader(string $key, array|string $value): void {
		$key = strtolower($key);
		$this->headers_parsed[$key] = $value;
	}

	/**
	 * Is this a POST?
	 *
	 * @return bool
	 */
	public function isPost(): bool {
		return $this->method === HTTP::METHOD_POST;
	}

	/**
	 * Get the method for this request
	 *
	 * @return string
	 */
	public function method(): string {
		return $this->method;
	}

	/**
	 * Set or get the method for this request
	 *
	 * @param string $method
	 * @return $this
	 * @throws Exception_Parameter
	 */
	public function setMethod(string $method): self {
		$method = strtoupper($method);
		if (!array_key_exists($method, HTTP::$methods)) {
			throw new Exception_Parameter('Unknown method in {method_name}({method}', [
				'method_name' => __METHOD__, 'method' => $method,
			]);
		}
		$this->method = HTTP::$methods[$method];
		return $this;
	}

	/**
	 * Set a variable associated with this request
	 *
	 * @param string $name Value to set
	 * @param string|array|null $value Value to set
	 * @param bool $overwrite Overwrite value only if it's not set alrady
	 * @return mixed
	 */
	public function set(string $name, string|array $value = null, bool $overwrite = true): self {
		if (!$overwrite && $this->__isset($name)) {
			return $this;
		}
		$this->__set($name, $value);
		return $this;
	}

	/**
	 * Retrieve a variable value
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __isset(string $key): bool {
		return array_key_exists($key, $this->requestData);
	}

	/**
	 * Retrieve a variable value
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed {
		return $this->requestData[$key] ?? null;
	}

	/**
	 * Universal setter
	 */
	public function __set(string $key, mixed $value): void {
		$this->requestData[$key] = $value;
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
	 * @param int $index
	 * @return array
	 * @throws Exception_Key
	 * @throws Exception_Upload
	 */
	public function file(string $name, int $index = 0): array {
		if (!array_key_exists($name, $this->files)) {
			throw new Exception_Key($name);
		}
		$files = $this->files[$name];
		$error = $files['error'] ?? null;
		if (is_array($error)) {
			foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
				$result[$k] = $files[$k][$index] ?? null;
			}
			$files = $result;
			$files['index'] = $index;
			$files['total'] = count($error);
			$error = $files['error'] ?? null;
		}
		if ($error !== null && $error !== UPLOAD_ERR_OK) {
			throw new Exception_Upload($error);
		}
		$path = $files['tmp_name'];
		if (!is_uploaded_file($path) && !($files['zesk-daemon'] ?? false)) {
			throw new Exception_Upload('invalid upload');
		}
		return $files;
	}

	/**
	 * Retrieve system variables for this request (method, url, etc.)
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			self::OPTION_METHOD => $this->method,
			self::OPTION_URI => $this->uri,
			self::OPTION_HEADERS => $this->headers,
			self::OPTION_COOKIES => $this->cookies,
			self::OPTION_DATA => $this->data(),
			self::OPTION_DATA_FILE => $this->dataFile,
			self::OPTION_REQUEST_DATA => $this->requestData,
			self::OPTION_FILES => $this->files,
			self::OPTION_URL => $this->url,
			self::OPTION_URL_PARTS => $this->urlParts,
			self::OPTION_USER_AGENT => $this->userAgent?->classify(),
			self::OPTION_IP => $this->ip,
			self::OPTION_SERVER_IP => $this->serverIP,
			self::OPTION_REMOTE_IP => $this->remoteIP,
			'initialized' => $this->init,
			'DEFAULT_URI' => self::DEFAULT_URI,
			'DEFAULT_IP' => self::DEFAULT_IP,
		];
	}

	/**
	 * Retrieve all REQUEST variables for this request.
	 * Does not include object attributes such as URL or others.
	 *
	 * @return array
	 */
	public function requestData(): array {
		return $this->requestData;
	}

	/**
	 * Get the URL, or set the URL and optionally the path
	 *
	 * @return string
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 * Set the URL and optionally the path
	 *
	 * @param string $set
	 * @return self
	 */
	public function setUrl(string $set): self {
		$this->url = $set;
		$this->urlParts = [];
		$this->_validURLParts();
		$this->uri = $this->deriveURI();
		return $this;
	}

	/**
	 * Get the path
	 *
	 * @return string
	 */
	public function path(): string {
		try {
			return $this->urlVariables('path');
		} catch (Exception_Key) {
			return '';
		}
	}

	/**
	 * Set the path on the server, updating the URL and parts
	 *
	 * @param string|null $set
	 * @return self
	 */
	public function setPath(string $set = null): self {
		$this->_validURLParts();
		$this->urlParts['path'] = $set;
		$this->setUrl(URL::stringify($this->urlParts));
		$this->uri = $this->deriveURI();
		return $this;
	}

	/**
	 * Return path + query string (if supplied)
	 *
	 * @return string
	 */
	public function uri(): string {
		return $this->uri;
	}

	/**
	 * Retrieve a segment of the request path
	 *
	 * @param int $index
	 * @param mixed $default
	 * @return string
	 */
	public function path_index(int $index, string $default = ''): string {
		return explode('/', $this->path())[$index] ?? $default;
	}

	/**
	 * Retrieve the current host
	 *
	 * @return string
	 */
	public function host(): string {
		$this->_validURLParts();
		return $this->urlParts['host'] ?? '';
	}

	/**
	 * Retrieve the current port
	 *
	 * @return integer
	 */
	public function port(): int {
		$this->_validURLParts();
		return intval($this->urlParts['port'] || URL::protocolPort($this->scheme()));
	}

	/**
	 * Retrieve the current scheme
	 *
	 * @return string
	 */
	public function scheme(): string {
		$this->_validURLParts();
		return $this->urlParts['scheme'] ?? 'http';
	}

	/**
	 * Retrieve the current query
	 *
	 * @return string
	 */
	public function query(): string {
		$this->_validURLParts();
		return $this->urlParts['query'] ?? '';
	}

	/**
	 * Retrieve the URL component
	 * @return array
	 */
	public function urlComponents(): array {
		return $this->urlParts;
	}

	/**
	 * Retrieve the URL component
	 * @param string $component
	 * @return array|string
	 * @throws Exception_Key
	 */
	public function urlComponent(string $component): ?string {
		if (array_key_exists($component, $this->urlParts)) {
			return $this->urlParts[$component];
		}

		throw new Exception_key($component);
	}

	/**
	 * Retrieve the URL component
	 *
	 * @param ?string $component
	 * @param mixed $default
	 * @return array|string
	 * @throws Exception_Key
	 */
	public function urlVariables(string $component = null, mixed $default = ''): string|array {
		$this->_validURLParts();
		if ($component === null) {
			return $this->urlComponents();
		}
		return $this->urlComponent($component) ?? $default;
	}

	/**
	 * Parse the range value
	 *
	 * @todo make this an object, maybe?
	 * @throws Exception_Key
	 */
	public function range_parse(): string {
		$range = $this->header('Range');

		$matches = null;
		preg_match_all('/(-?[0-9]++(?:-(?![0-9]++))?)(?:-?([0-9]++))?/', $range, $matches, PREG_SET_ORDER);

		return $matches[0];
	}

	/**
	 * Is this likely a web browser?
	 *
	 * @return boolean
	 */
	public function isBrowser(): bool {
		try {
			return $this->header(HTTP::REQUEST_USER_AGENT) !== null;
		} catch (Exception_Key) {
			return false;
		}
	}

	/**
	 * Return user agent object
	 *
	 * @return UserAgent
	 */
	public function userAgent(): UserAgent {
		if (!$this->userAgent instanceof UserAgent) {
			try {
				$uaString = $this->header(HTTP::REQUEST_USER_AGENT);
			} catch (Exception_Key) {
				$uaString = '';
			}
			$this->userAgent = new UserAgent($uaString);
		}
		return $this->userAgent;
	}

	/**
	 * Retrieve the IP address of the request, taking proxy server headers into consideration.
	 *
	 * @return string
	 */
	public function ip(): string {
		return $this->ip;
	}

	/**
	 * Retrieve the IP address of the request, ignoring any proxy servers
	 *
	 * @return string
	 */
	public function remoteIP(): string {
		return $this->remoteIP;
	}

	/**
	 * Retrieve the server IP address
	 *
	 * @return mixed
	 */
	public function serverIP(): string {
		return $this->serverIP;
	}

	/**
	 * Retrieve the referrer
	 *
	 * @return string
	 */
	public function referrer(): string {
		try {
			return $this->header(HTTP::REQUEST_REFERRER);
		} catch (Exception_Key) {
			return '';
		}
	}

	/**
	 * @param string $check
	 * @return bool
	 */
	public function userAgentIs(string $check): bool {
		return $this->userAgent()->is($check);
	}

	/**
	 * Output string value which can be passed to new Request($request->__toString())
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		return PHP::dump($this->variables());
	}

	/**
	 *
	 * @see http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
	 * @return array
	 */
	public static function maxUploadSizes(): array {
		$result = [];
		foreach (['upload_max_filesize', 'post_max_size', 'memory_limit'] as $iniSetting) {
			$result[$iniSetting] = toBytes(ini_get($iniSetting));
		}
		$min_key = $min_value = null;
		foreach ($result as $key => $value) {
			if ($min_key === null || $value < $min_value) {
				$min_key = $key;
				$min_value = $value;
			}
		}
		return $result + [
			'limiting_factor' => $min_key,
		];
	}

	/**
	 * @see http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
	 * @return int
	 */
	public static function maxUploadSize(): int {
		$result = self::maxUploadSizes();
		return $result[$result['limiting_factor']];
	}

	/**
	 * Retrieve a cookie from the request
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception_Key
	 */
	public function cookie(string $name): string {
		if (array_key_exists($name, $this->cookies)) {
			return $this->cookies[$name];
		}

		throw new Exception_Key($name);
	}

	/**
	 * Retrieve a cookie from the request
	 *
	 * @return array
	 */
	public function cookies(): array {
		return $this->cookies;
	}

	/**
	 * Ensure that ->urlParts is available to be read
	 */
	private function _validURLParts(): void {
		if (count($this->urlParts)) {
			return;
		}

		try {
			$parts = URL::parse($this->url);
		} catch (Exception_Syntax) {
			$parts = ['error' => 'syntax'];
		}
		$this->urlParts = $parts + [
			'url' => $this->url, 'scheme' => 'http', 'host' => 'localhost', 'port' => 80, 'path' => '',
		];
	}

	/**
	 * Retrieve the default request
	 *
	 * @return array
	 */
	private function defaultRequestData(): array {
		if ($this->method === HTTP::METHOD_PUT) {
			// Support JSON
			return $this->data() + $_GET;
		}
		if ($this->method === HTTP::METHOD_POST) {
			// Support JSON
			return $this->data() + $_GET;
		}
		return is_array($_REQUEST) ? $_REQUEST : [];
	}

	/**
	 * Convert server variables into HTTP headers
	 *
	 * @param array $server
	 * @return array
	 */
	private static function httpHeadersFromSERVER(array $server): array {
		$server = ArrayTools::keysReplace(array_change_key_case($server), '_', '-');
		$headers = [];
		foreach ($server as $key => $value) {
			foreach ([
				'http-' => true, 'content-' => false,
			] as $prefix => $removePrefix) {
				$len = strlen($prefix);
				if (substr($key, 0, $len) === $prefix) {
					$headers[$removePrefix ? substr($key, $len) : $key] = $value;
				}
			}
		}
		return $headers;
	}

	/**
	 * Format the path + query string into a single string
	 *
	 * @return string
	 */
	private function deriveURI(): string {
		return $this->query() ? URL::queryFormat($this->path() . $this->query()) : $this->path();
	}

	/**
	 * Given a $_SERVER structure, extract the URL parts and generate the complete URL
	 *
	 * @param array $server
	 * @return string
	 */
	private function urlFromSERVER(array $server): string {
		$parts['scheme'] = $this->currentScheme($server);
		$parts['host'] = $this->currentHost();
		$parts['port'] = $this->currentPort($server);
		$parts['path'] = $this->currentURI($server);
		return URL::stringify($parts);
	}

	/**
	 * Extract the request scheme, supporting the X-Forwarded-Proto passed in by load balancers which
	 * represents the original protocol.
	 *
	 * @param array $server
	 * @return string
	 */
	private function currentScheme(array $server): string {
		// Amazon load balancers
		try {
			return $this->header('X-Forwarded-Proto');
		} catch (Exception_Key) {
			return ($server['HTTPS'] ?? null) === 'on' ? 'https' : 'http';
		}
	}

	/**
	 * @return string
	 */
	private function currentHost(): string {
		try {
			$host = $this->header('Host');
			return strtolower(StringTools::left($host, ':', $host));
		} catch (Exception_Key) {
			return '';
		}
	}

	/**
	 * Extract the request port, supporting the X-Forwarded-Port passed in by load balancers which
	 * represents the original port.
	 *
	 * @param array $server
	 * @return int
	 */
	private function currentPort(array $server): int {
		try {
			// Amazon load balancers
			$port = $this->header('X-Forwarded-Port');
			if ($port) {
				return intval($port);
			}
		} catch (Exception_Key) {
		}
		return intval($server['SERVER_PORT'] ?? 80);
	}

	/**
	 * Return the Request URI
	 * @param array $server
	 * @return string
	 */
	private function currentURI(array $server): string {
		return $server['REQUEST_URI'] ?? '';
	}

	/**
	 * Does the current request prefer a JSON response?
	 *
	 * @return boolean
	 */
	public function preferJSON(): bool {
		return $this->acceptPriority([
			'application/json', 'text/html',
		]) === 'application/json';
	}

	/**
	 * Helper function for self::remote.
	 * Searches an array for a valid IP address.
	 *
	 * @param array $server An array to search for certain keys
	 * @return string
	 */
	private static function _findRemoteKey(array $server): string {
		$ks = [
			'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR',
		];
		foreach ($ks as $k) {
			if (!isset($server[$k])) {
				continue;
			}
			$ip = $server[$k];
			if ($ip === 'unknown') {
				continue;
			}
			if (empty($ip)) {
				continue;
			}
			$match = false;
			if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip, $match)) {
				return strval($match[0]);
			}
		}
		return self::DEFAULT_IP;
	}

	/**
	 *
	 * @param string $check Check value for user agent
	 * @return bool
	 * @deprecated 2022-12
	 */
	public function user_agent_is(string $check): bool {
		$this->application->deprecated(__METHOD__);
		return $this->userAgentIs($check);
	}

	/**
	 * Is this used anywhere?
	 *
	 * @return bool
	 * @deprecated 2022-12
	 */
	public function isAjax(): bool {
		$this->application->deprecated(__METHOD__);
		if ($this->getBool('ajax')) {
			return true;
		}
		return $this->get('ajax_id', null) !== null;
	}
}
