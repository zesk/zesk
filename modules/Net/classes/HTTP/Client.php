<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

namespace zesk\Net\HTTP;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Command;
use zesk\Exception_Connect;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_DomainLookup;
use zesk\Exception_File_Permission;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception_Unsupported;
use zesk\File;
use zesk\Hookable;
use zesk\HTTP;
use zesk\Kernel;
use zesk\Net\HTTP\Client\Cookie;
use zesk\Request;
use zesk\Response;
use zesk\StringTools;
use zesk\Timestamp;
use zesk\URL;
use zesk\Version;

use zesk\Net\HTTP\Client\Exception as ClientException;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Client extends Hookable {
	public const OPTION_FOLLOW_LOCATION = 'setFollowLocation';

	/*
	 * Sample user agent for FireFox
	 * @var string
	 */
	public const USER_AGENT_FIREFOX = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.7; en-US; rv:1.9.0.4) Gecko/2009032609  Firefox/3.0.8';

	/*
	 * Sample user agent for Microsoft Internet Explorer
	 * @var string
	 */
	public const USER_AGENT_INTERNET_EXPLORER = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0; .NET4.0E; .NET4.0C; BRI/2)';

	/**
	 * Sample user agent for Safari
	 *
	 * @var string
	 */
	public const USER_AGENT_SAFARI = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1795.2 Safari/537.36';

	/**
	 * Sample user agent for Chrome
	 *
	 * @var string
	 */
	public const USER_AGENT_CHROME = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1796.0 Safari/537.36';

	/**
	 * Sample user agents
	 *
	 * @var array
	 */
	public static array $sample_agents = [
		self::USER_AGENT_CHROME, self::USER_AGENT_FIREFOX, self::USER_AGENT_INTERNET_EXPLORER, self::USER_AGENT_SAFARI,
	];

	/**
	 * @var string
	 */
	protected string $url = '';

	/**
	 * @var array
	 */
	protected array $urlParts = [];

	/**
	 * Cookies to send
	 *
	 * @var array:string
	 */
	private array $requestCookie = [];

	/**
	 *
	 * @var array
	 */
	private array $requestHeaders = [];

	/**
	 *
	 * @var array
	 */
	private array $skipRequestHeaders = [];

	/**
	 * lowname => value
	 *
	 * @var array
	 */
	private array $responseHeaders = [];

	/**
	 *
	 * @var bool|string
	 */
	private bool|string $content;

	/**
	 *
	 * @var string
	 */
	private string $responseProtocol;

	/**
	 *
	 * @var int
	 */
	private int $responseCode;

	/**
	 *
	 * @var string
	 */
	private string $responseMessage;

	/**
	 *
	 * @var array
	 */
	private array $responseCookies = [];

	/**
	 * Connection timeout in milliseconds
	 *
	 * @var int
	 */
	private int $connectTimeout = 5000;

	/**
	 * @var string
	 */
	private string $method = HTTP::METHOD_GET;

	/**
	 * @var string
	 */
	private string $data = '';

	/**
	 * File resource
	 *
	 * @var mixed
	 */
	private mixed $dataFile = null;

	/**
	 * Curl retrieval timeout in milliseconds
	 *
	 * @var integer
	 */
	private int $timeout = 5000;

	/**
	 * Whether to recurse when redirected
	 *
	 * @var boolean
	 */
	private bool $recurse = false;

	/**
	 * The user agent used for the connection
	 */
	private string $userAgent = '';

	/**
	 * Path of the destination file
	 *
	 * @var string
	 */
	private string $destination = '';

	/**
	 * CURL options
	 *
	 * @var array
	 */
	private array $curl_opts = [];

	/**
	 * Error with connecting to server
	 */
	public const ERROR_CONNECTION = 'Error_Connection';

	/**
	 * Error resolving host name
	 */
	public const ERROR_RESOLVE_HOST = 'Error_Resolve_Host';

	/**
	 * Error waiting for server to respond
	 */
	public const ERROR_TIMEOUT = 'Error_Timeout';

	/**
	 * Error connecting via SSL to remote site
	 */
	public const ERROR_SSL_CONNECT = 'Error_SSL_Connect';

	/**
	 * For proxy
	 *
	 * @var array
	 */
	public static array $ignore_response_headers = [
		HTTP::RESPONSE_CONTENT_ENCODING => true, HTTP::RESPONSE_TRANSFER_ENCODING => true,
	];

	/**
	 * Create a new Net_HTTP_Client
	 *
	 * @param Application $application
	 * @param string $url
	 * @param array $options
	 */
	public function __construct(Application $application, string $url = '', array $options = []) {
		parent::__construct($application, $options);

		$this->inheritConfiguration();
		$this->loadFromOptions();
		if ($url) {
			$this->setURL($url);
		}
		if (!$this->userAgent) {
			$this->setUserAgent($this->defaultUserAgent());
		}
	}

	public function application(): Application {
		return $this->application;
	}

	private function loadFromOptions(): void {
		if ($this->hasOption('timeout')) {
			$this->setTimeout($this->optionInt('timeout'));
		}
		if ($this->hasOption('user_agent')) {
			$this->setUserAgent($this->option('user_agent'));
		}
	}

	public const OPTION_DEFAULT_USER_AGENT = 'default_user_agent';

	/**
	 * The default user agent
	 *
	 * @return string
	 */
	public function defaultUserAgent(): string {
		return $this->option(self::OPTION_DEFAULT_USER_AGENT, __CLASS__ . ' ' . Version::release());
	}

	/**
	 * Get/set POST method
	 *
	 * @return Client
	 */
	public function setMethodPost(): self {
		return $this->setMethod(HTTP::METHOD_POST);
	}

	/**
	 * Get/set PUT method
	 *
	 * @return Client
	 */
	public function setMethodPUT(): self {
		return $this->setMethod(HTTP::METHOD_PUT);
	}

	/**
	 * Get/set POST method
	 *
	 * @return Client
	 */
	public function setMethodHead(): self {
		return $this->setMethod(HTTP::METHOD_HEAD);
	}

	/**
	 * Get/set the data associated with this client
	 *
	 * @return mixed
	 */
	public function data(): string {
		return $this->data;
	}

	public function setData(string $set): self {
		$this->data = $set;
		return $this;
	}

	/**
	 * Get the URL associated with this HTTP client
	 *
	 */
	public function url(): string {
		return $this->url;
	}

	public function setURL(string $set): self {
		$this->urlParts = URL::parse($set);
		$this->url = $set;
		return $this;
	}

	/**
	 * Set the filename path where to store the data
	 *
	 * @param string $set
	 * @return self
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_NotFound
	 */
	public function setDestination(string $set): self {
		$this->destination = File::validateWritable($set);
		return $this;
	}

	/**
	 * Get the filename path where to store the data
	 *
	 * @return string
	 */
	public function destination(): string {
		return $this->destination;
	}

	/**
	 * Return the full error code (404,200,etc.)
	 *
	 * @return int
	 */
	public function response_code(): int {
		return $this->responseCode;
	}

	/**
	 * Return the base error type 2,3,4,5
	 *
	 * @return int
	 */
	public function response_code_type(): int {
		$code = strval($this->responseCode);
		return intval(strval($code[0]));
	}

	public function response_message(): string {
		return $this->responseMessage;
	}

	/**
	 *
	 * @return $ResponseProtocol
	 */
	public function response_protocol() {
		return $this->responseProtocol;
	}

	/**
	 * Get or set request cookies
	 *
	 * @param array $set
	 *            Set cookies to name/value pairs for request
	 * @param boolean $append
	 */
	public function request_cookie(array $set = null, $append = false) {
		if ($set === null) {
			return $this->requestCookie;
		}
		$this->requestCookie = $append ? $set + $this->requestCookie : $set;
		return $this;
	}

	/**
	 * Format request cookies
	 *
	 * @return string
	 */
	private function format_cookie() {
		// semicolon, comma, and white space
		$encode = [];
		foreach (str_split(";,= \r\n", 1) as $char) {
			$encode[$char] = urlencode($char);
		}
		$result = [];
		foreach ($this->requestCookie as $name => $value) {
			if ($value === true) {
				$result[] = strtr($name, $encode);
			} else {
				$result[] = strtr($name, $encode) . '=' . strtr($value, $encode);
			}
		}
		return implode('; ', $result);
	}

	/**
	 * Retrieve a request header
	 *
	 * @param string $name
	 * @param string $set
	 * @return self
	 */
	public function setRequestHeader(string $name, string $set): self {
		$lowName = strtolower($name);
		$this->requestHeaders[$lowName] = $set;
		return $this;
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws Exception_Key
	 */
	public function requestHeader(string $name): string {
		$lowName = strtolower($name);
		if (array_key_exists($lowName, $this->requestHeaders)) {
			return $this->requestHeaders[$lowName];
		}

		throw new Exception_Key($name);
	}

	/**
	 * When an HTTP header is handled by a curl option, add it here so it's not sent twice.
	 *
	 * Not sure how smart curl is, but probably better not to be redundant.
	 *
	 * @param string $name
	 */
	private function skip_request_header(string $name): void {
		$name = strtolower($name);
		$this->skipRequestHeaders[$name] = $name;
	}

	/**
	 * Retrieve the response content type
	 *
	 * @return string
	 */
	public function content_type() {
		$header = $this->responseHeaders['content-type'] ?? null;
		if (!$header) {
			return null;
		}
		$header = trim(StringTools::left($header, ';', $header));
		return $header;
	}

	/**
	 * Get/set the request timeout in miiliseconds
	 *
	 * @return int
	 */
	public function timeout(): int {
		return $this->timeout;
	}

	/**
	 * Set the request timeout in miiliseconds
	 *
	 * @param int $milliseconds
	 * @return self
	 */
	public function setTimeout(int $milliseconds): self {
		$this->timeout = $milliseconds;
		return $this;
	}

	/**
	 * Get the request timeout in miiliseconds
	 *
	 * @return int
	 */
	public function connectTimeout(): int {
		return $this->connectTimeout;
	}

	/**
	 * Get the request timeout in miiliseconds
	 *
	 * @param int $milliseconds
	 * @return self
	 */
	public function setConnectTimeout(int $milliseconds): self {
		$this->connectTimeout = $milliseconds;
		return $this;
	}

	/**
	 * Get the method
	 *
	 * @return string
	 */
	public function method(): string {
		return $this->method;
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setMethod(string $set): self {
		$this->method = strtoupper($set);
		return $this;
	}

	public function validMethod(): bool {
		return array_key_exists($this->method, HTTP::$methods);
	}

	/**
	 * @return void
	 */
	private function _zero_content_length_warning(): void {
		$this->application->logger->warning('{method} with 0 size data', ['method' => $this->method]);
	}

	/**
	 *
	 */
	public const OPTION_VERIFY_SSL = 'VerifySSL';

	/**
	 *
	 */
	public const DEFAULT_OPTION_VERIFY_SSL = false;

	/**
	 * Initialize our curl options before executing the curl object
	 */
	private function _methodOpen(): array {
		$httpHeaders = [];
		$this->curl_opts = [
			CURLOPT_ENCODING => 1, CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => $this->optionBool(self::OPTION_VERIFY_SSL, self::DEFAULT_OPTION_VERIFY_SSL),
		];
		$data = strval($this->_encodeData());
		$length = strlen($data);
		switch ($this->method) {
			case HTTP::METHOD_GET:
				break;
			case HTTP::METHOD_POST:
				if ($length === 0) {
					$this->_zero_content_length_warning();
				}
				$this->curl_opts[CURLOPT_POST] = 1;
				$this->curl_opts[CURLOPT_POSTFIELDS] = $data;
				$httpHeaders[] = 'Content-Length: ' . $length;
				$this->skip_request_header('content-length');

				break;
			case HTTP::METHOD_HEAD:
				$this->curl_opts[CURLOPT_NOBODY] = 1;

				break;
			case HTTP::METHOD_PUT:
				if ($length === 0) {
					$this->_zero_content_length_warning();
				}
				$this->dataFile = tmpfile();
				fwrite($this->dataFile, $data);
				fseek($this->dataFile, 0);
				$this->curl_opts[CURLOPT_PUT] = true;
				$this->curl_opts[CURLOPT_INFILE] = $this->dataFile;
				$this->curl_opts[CURLOPT_INFILESIZE] = $length;
				$httpHeaders[] = 'Content-Length: ' . $length;
				$this->skip_request_header('content-length');

				break;
			default:
				$this->curl_opts[CURLOPT_CUSTOMREQUEST] = $this->method;

				break;
		}
		return $httpHeaders;
	}

	/**
	 * Close our curl options after curl has completed
	 */
	private function _method_close(): void {
		if ($this->dataFile) {
			fclose($this->dataFile);
		}
		if ($this->destination) {
			fclose($this->curl_opts[CURLOPT_FILE]);
			fclose($this->curl_opts[CURLOPT_WRITEHEADER]);
		}
	}

	/**
	 * Set curl options related to timeouts and network activity
	 */
	private function _curl_opts_timeouts(): void {
		if ($this->connectTimeout > 0) {
			if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
				$this->curl_opts[CURLOPT_CONNECTTIMEOUT_MS] = $this->connectTimeout;
			} else {
				$this->curl_opts[CURLOPT_CONNECTTIMEOUT] = intval($this->connectTimeout / 1000);
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
	private function _curl_opts_if_modified(): void {
		try {
			$value = $this->requestHeader('If-Modified-Since');
			$this->curl_opts[CURLOPT_TIMECONDITION] = CURL_TIMECOND_IFMODSINCE;
			$this->curl_opts[CURLOPT_TIMEVALUE] = $value;
			$this->skip_request_header('If-Modified-Since');
		} catch (Exception_Key $e) {
		}
	}

	/**
	 * Set curl options related to User-Agent
	 */
	private function _curl_opts_useragent(): void {
		try {
			$this->curl_opts[CURLOPT_USERAGENT] = $this->requestHeader(HTTP::REQUEST_USER_AGENT);
			$this->skip_request_header(HTTP::REQUEST_USER_AGENT);
		} catch (Exception_Key $e) {
		}
	}

	/**
	 * Set curl options related to the method
	 */
	private function _curl_opts_method(): void {
		$returnHeaders = $this->wantHeaders();
		$is_head = $this->method() === HTTP::METHOD_HEAD;
		if ($is_head) {
			$returnHeaders = $this->setWantHeaders(true);
			$this->curl_opts[CURLOPT_NOBODY] = 1;
		}
		if ($returnHeaders) {
			$this->curl_opts[CURLOPT_HEADER] = 1;
		} else {
			$this->curl_opts[CURLOPT_HEADER] = 0;
		}
	}

	private function _curl_opts_follow(): void {
		if ($this->recurse) {
			$this->curl_opts[CURLOPT_FOLLOWLOCATION] = true;
		}
		if ($this->optionBool('setFollowLocation')) {
			$this->curl_opts[CURLOPT_FOLLOWLOCATION] = true;
			$this->curl_opts[CURLOPT_MAXREDIRS] = $this->optionInt('setFollowLocation_maximum', 7);
		}
	}

	private function _curl_opts_host(): void {
		$parts = parse_url($this->url());
		$host = $parts['host'] ?? null;
		$scheme = $parts['scheme'] ?? null;
		$default_port = URL::protocolPort($scheme);
		$port = intval($parts['port'] ?? $default_port);
		if ($port !== $default_port) {
			$host .= ":$port";
		}
		$this->setRequestHeader('Host', $host);
	}

	private function _curl_opts_cookie(): void {
		if (count($this->requestCookie)) {
			$this->setRequestHeader('Cookie', $this->format_cookie());
		}
	}

	private function _curl_opts_range(): void {
		try {
			$range = $this->requestHeader('Range');
			$this->curl_opts[CURLOPT_RANGE] = substr($range, 6);
			$this->skip_request_header('Range');
		} catch (Exception_Key $e) {
		}
	}

	private function _curl_opts_headers(): void {
		foreach ($this->requestHeaders as $k => $values) {
			if (!array_key_exists(strtolower($k), $this->skipRequestHeaders)) {
				if (is_string($values)) {
					$values = [
						$values,
					];
				}
				$k = HTTP::$request_headers[$k] ?? $k;
				foreach ($values as $value) {
					$httpHeaders[] = "$k: $value";
				}
			}
		}
		$this->curl_opts[CURLOPT_HTTPHEADER] = $httpHeaders;
	}

	/**
	 * Update CURL settings for a target file to store headers
	 *
	 * @return string
	 * @throws Exception_File_Permission
	 */
	private function _curlOptionsDestination(): string {
		if (!$this->destination) {
			return '';
		}
		$dest_fp = fopen($this->destination, 'wb');
		if (!$dest_fp) {
			throw new Exception_File_Permission($this->destination, 'Not writable');
		}
		$this->curl_opts[CURLOPT_FILE] = $dest_fp;
		$dest_headers_name = $this->destination . '-headers';
		$dest_headers_fp = fopen($dest_headers_name, 'wb');
		$this->curl_opts[CURLOPT_WRITEHEADER] = $dest_headers_fp;

		return $dest_headers_name;
	}

	private function _curl_opts_close_destination(): void {
	}

	private function _parse_headers(string $dest_headers_name): void {
		if ($dest_headers_name) {
			$all_headers = file_get_contents($dest_headers_name);
			$headers_list = explode("\r\n\r\n", $all_headers);
			$this->responseHeaders = [];
			foreach ($headers_list as $headers) {
				if (empty($headers)) {
					break;
				}
				$this->parseHeaders($headers);
			}
			unlink($dest_headers_name);
			File::trim($this->destination, strlen($all_headers));
		} elseif ($this->wantHeaders()) {
			$this->parseHeaders();
		}
	}

	/**
	 * @return bool|string
	 * @throws Exception_DomainLookup
	 * @throws Exception_File_Permission
	 * @throws Exception_Parameter
	 * @throws Exception_Syntax
	 * @throws Exception_Unsupported
	 * @throws ClientException
	 */
	public function go() {
		if (!function_exists('curl_init')) {
			throw new Exception_Unsupported('Net_HTTP_Client::go(): CURL not integrated!');
		}
		if (empty($this->url)) {
			throw new Exception_Parameter('Net_HTTP_Client::go called with no URL specified');
		}
		$url = $this->url;

		$httpHeaders = $this->_methodOpen();
		$this->_curl_opts_method();
		$this->_curl_opts_timeouts();
		$this->_curl_opts_if_modified();
		$this->_curl_opts_useragent();
		$this->_curl_opts_follow();
		$this->_curl_opts_host();
		$this->_curl_opts_cookie();
		$this->_curl_opts_headers();
		$dest_headers_name = $this->_curlOptionsDestination();

		if ($this->option('debug')) {
			dump($url);
			dump($httpHeaders);
		}
		// Supress "Operation timed out after 5003 milliseconds with 0 bytes received"
		$curl = curl_init($url);
		foreach ($this->curl_opts as $option => $value) {
			curl_setopt($curl, $option, $value);
		}
		$this->content = @curl_exec($curl);
		$this->_method_close($curl);
		$errno = curl_errno($curl);
		$error_code = curl_error($curl);

		$this->_parse_headers($dest_headers_name);

		if ($this->optionBool('debug') && $this->destination) {
			if (file_exists($this->destination)) {
				$command = Command::running();
				if ($command) {
					$command->readline(__CLASS__ . ' : CHECK ' . $this->url() . " Destination $this->destination");
				}
			}
		}

		curl_close($curl);
		if ($errno !== 0) {
			if ($errno === CURLE_COULDNT_RESOLVE_HOST) {
				$host = $this->urlParts['host'] ?? '';

				throw new Exception_DomainLookup($host, 'Retrieving URL {url}', [
					'url' => $this->url(),
				], $errno);
			}
			// TODO 2017-08 These should probably all be their own Exception class
			$errno_map = [
				CURLE_COULDNT_CONNECT => self::ERROR_CONNECTION, CURLE_COULDNT_RESOLVE_HOST => self::ERROR_RESOLVE_HOST,
				CURLE_OPERATION_TIMEOUTED => self::ERROR_TIMEOUT, CURLE_SSL_CONNECT_ERROR => self::ERROR_SSL_CONNECT,
			];
			$error_string = $errno_map[$errno] ?? "UnknownErrno-$errno";

			throw new ClientException('Error {error_code} ({errno} = {error_string})', [
				'error_string' => $error_string,
			], $errno, $error_code);
		}
		return $this->content;
	}

	/**
	 * @param string $url
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Connect
	 */
	public static function simpleGet(string $url): string {
		if (!$url) {
			throw new Exception_Semantics('Require non-blank URL');
		}
		$parts = parse_url($url);
		$protocol = $parts['scheme'] ?? '';
		if (!in_array($protocol, [
			'http', 'https',
		])) {
			throw new Exception_Semantics('Require valid HTTP URL {protocol} ({url})', [
				'protocol' => $protocol, 'url' => $url,
			]);
		}
		$ctx_options = [
			'http' => [
				'user_agent' => self::$sample_agents[0],
			],
		];
		$cafile = ZESK_ROOT . 'etc/cacert.pem';
		if (!is_file($cafile)) {
			$ctx_options['ssl'] = [
				'verify_peer' => false,
			];
		} else {
			$ctx_options['ssl'] = [
				'verify_peer' => true, 'cafile' => $cafile,
			];
		}
		$context = stream_context_create($ctx_options);
		$f = fopen($url, 'rb', false, $context);
		if (!$f) {
			$host = $parts['host'] ?? '';

			throw new Exception_Connect($host);
		}
		$contents = '';
		while (!feof($f)) {
			$contents .= fread($f, 4096);
		}
		return $contents;
	}

	public function domain() {
		$url = $this->option('URL');
		return URL::host($url);
	}

	public static function url_content_length(Application $application, $url) {
		$headers = self::url_headers($application, $url);
		return toInteger($headers[ 'Content-Length'] ?? null);
	}

	public static function url_headers(Application $application, $url): array {
		$x = new self($application, $url);
		$x->setMethodHead();
		$x->go();
		$result = $x->response_code_type();
		if ($result !== 2) {
			throw new ClientException('{method}({url}) returned response code {result} ', [
				'method' => __METHOD__, 'ur' => $url, 'result' => $x->response_code(),
			]);
		}
		return $x->responseHeaders();
	}

	private function _encodeData(): string {
		$data = $this->data;
		if (is_string($data)) {
			return $data;
		}
		if (!is_array($data)) {
			throw new Exception_Semantics('Data is not a string or array?');
		}
		return http_build_query($data);
	}

	/**
	 *
	 * @return array
	 */
	public function responseCookies(): array {
		return $this->responseCookies;
	}

	/**
	 * @param Cookie $cookie
	 * @return $this
	 */
	public function setResponseCookies(Cookie $cookie): self {
		$this->responseCookies[] = $cookie;
		return $this;
	}

	/*
	 * Cookie Handling
	 * @todo move this out of here, use a Cookie Jar or something
	 */
	private function cookieString(string $url): string {
		$parts = URL::parse($url);
		$host = strtolower($parts['host'] ?? '');
		$path = $parts['path'] ?? '/';
		$secure = strtolower(($parts['scheme'] ?? '')) === 'https';
		$results = [];
		foreach ($this->responseCookies as $cookies) {
			if (!is_array($cookies)) {
				$cookies = [
					$cookies,
				];
			}
			foreach ($cookies as $cookie) {
				/* @var $cookie Cookie */
				if ($cookie->matches($host, $path)) {
					if (!$secure && $cookie->secure()) {
						continue;
					}
					$results[] = $cookie->string();
				}
			}
		}
		if (empty($results)) {
			return '';
		}
		return implode('; ', $results);
	}

	/**
	 * @param string $cookieName
	 * @param string $domain
	 * @param string $path
	 * @return bool
	 */
	private function deleteCookie(string $cookieName, string $domain, string $path): bool {
		if (!isset($this->responseCookies[$cookieName])) {
			return false;
		}
		$cookies = $this->responseCookies[$cookieName];
		if (is_array($cookies)) {
			foreach ($cookies as $k => $cookie) {
				assert($cookie instanceof Net_HTTP_Client_Cookie);
				if ($cookie->matches($domain, $path)) {
					unset($this->responseCookies[$cookieName][$k]);
					return true;
				}
			}
		} else {
			$cookie = $cookies;
			assert($cookie instanceof Net_HTTP_Client_Cookie);
			if ($cookie->matches($domain, $path)) {
				unset($this->responseCookies[$cookieName]);
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $cookieName
	 * @param string $domain
	 * @param string $path
	 * @return Cookie
	 * @throws Exception_NotFound
	 */
	private function findCookie(string $cookieName, string $domain, string $path): Cookie {
		if (isset($this->responseCookies[$cookieName])) {
			$cookies = $this->responseCookies[$cookieName];
			if (is_array($cookies)) {
				foreach ($cookies as $cookie) {
					assert($cookie instanceof Cookie);
					if ($cookie->matches($domain, $path)) {
						return $cookie;
					}
				}
			} else {
				$cookie = $cookies;
				assert($cookie instanceof Cookie);
				if ($cookie->matches($domain, $path)) {
					return $cookie;
				}
			}
		}

		throw new Exception_NotFound('Cookie {cookieName}', [
			'cookieName' => $cookieName, 'domain' => $domain, 'path' => $path,
		]);
	}

	private function addCookie(string $cookieName, string $cookieValue, string $domain = '', string $path = '', int|Timestamp $expires = 0, bool $secure = false): void {
		if (!$domain) {
			$domain = $this->domain();
		}
		if (!$path) {
			$path = '/';
		}
		ArrayTools::append($this->responseCookies, $cookieName, new Cookie($cookieName, $cookieValue, $domain, $path, $expires, $secure));
	}

	private function parseCookies(): bool {
		if (!isset($this->responseHeaders['set-cookie'])) {
			return false;
		}
		$cookies = $this->responseHeaders['set-cookie'];
		if (!is_array($cookies)) {
			$cookies = [
				$cookies,
			];
		}
		foreach ($cookies as $cookie) {
			$parts = explode(';', $cookie);
			$cookie_item = array_shift($parts);
			[$cookieName, $cookieValue] = StringTools::pair($cookie_item, '=', $cookie_item, null);
			$cookieName = trim($cookieName);
			if (empty($cookieName)) {
				continue;
			}
			$path = '/';
			$secure = false;
			$domain = $this->domain();
			$expireString = false;
			foreach ($parts as $cname) {
				[$cname, $cvalue] = StringTools::pair($cname, '=', $cname);
				$cname = strtolower(trim($cname));
				$cvalue = trim($cvalue);
				switch ($cname) {
					case 'path':
						$path = $cvalue;

						break;
					case 'secure':
						$secure = true;

						break;
					case 'domain':
						$domain = $cvalue;

						break;
					case 'expires':
						$expireString = $cvalue;

						break;
				}
			}
			$expires = 0;
			$deleteCookie = false;

			if ($expireString) {
				try {
					$expires = Timestamp::factory($expireString);
					$now = Timestamp::now();
					if ($expires->before($now)) {
						$deleteCookie = true;
					}
				} catch (Exception_Semantics) {
				}
			}
			if ($deleteCookie) {
				$this->deleteCookie($cookieName, $domain, $path);
			} else {
				try {
					$this->findCookie($cookieName, $domain, $path)->update($cookieValue, $expires);
				} catch (Exception_NotFound) {
					$this->addCookie($cookieName, $cookieValue, $domain, $path, $expires, $secure);
				}
			}
		}
		return true;
	}

	/**
	 * Getter for recurse flag
	 *
	 * @return bool
	 */
	public function recurse(): bool {
		return $this->recurse;
	}

	/**
	 * Setter for recurse flag
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setRecurse(bool $set): self {
		$this->recurse = $set;
		return $this;
	}

	public function content(): string {
		return $this->content;
	}

	/**
	 * @param string $content
	 * @return void
	 */
	private function parseHeaders(string $content = ''): void {
		$headers = ($content === '') ? $this->content : $content;
		$this->responseHeaders = [];
		[$headers, $new_content] = pair($headers, "\r\n\r\n", $headers, '');
		$headers = explode("\r\n", $headers);
		$response = explode(' ', array_shift($headers), 3);
		$this->responseProtocol = array_shift($response);
		$this->responseCode = intval(array_shift($response));
		$this->responseMessage = array_shift($response);
		foreach ($headers as $h) {
			if (empty($h)) {
				continue;
			}
			[$h, $v] = pair($h, ':');
			$h = trim($h);
			ArrayTools::append($this->responseHeaders, strtolower($h), ltrim($v));
		}
		$this->parseCookies();
		if ($content === '') {
			$this->content = $new_content;
		}
	}

	/**
	 * All response headers
	 */
	public function responseHeaders(): array {
		return ArrayTools::keysMap($this->responseHeaders, HTTP::$response_headers);
	}

	/**
	 * Getter only
	 *
	 * @param string $name
	 */
	public function responseHeader(string $name): string {
		return $this->responseHeaders[strtolower($name)] ?? '';
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setFollowLocation(bool $set): self {
		$this->setOption(self::OPTION_FOLLOW_LOCATION, $set);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function followLocation(): bool {
		return $this->optionBool(self::OPTION_FOLLOW_LOCATION);
	}

	/**
	 * Getter for User-Agent for request
	 *
	 * @return string
	 */
	public function userAgent(): string {
		return $this->requestHeader(HTTP::REQUEST_USER_AGENT);
	}

	/**
	 * Getter/setter for User-Agent for request
	 *
	 * @param string $set
	 * @return self
	 */
	public function setUserAgent(string $set): self {
		$this->setRequestHeader(HTTP::REQUEST_USER_AGENT, $set);
		return $this;
	}

	/**
	 * Retrieve the filename of the file per the Content-Disposition header
	 *
	 * @return string
	 */
	public function filename(): string {
		// Content-Disposition: attachment; filename=foo.tar.gz
		$dispositions = ArrayTools::listTrimClean(explode(';', $this->responseHeader(HTTP::RESPONSE_CONTENT_DISPOSITION, '')));
		while (($disposition = array_shift($dispositions)) !== null) {
			[$name, $value] = pair($disposition, '=');
			if ($name === 'filename') {
				return unquote($value);
			}
		}
		return basename(URL::path($this->url()));
	}

	public const OPTION_RETURN_HEADERS = 'ReturnHeaders';

	/**
	 * Get/Set that we want to retrieve the headers from the remote server
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setWantHeaders(bool $set): self {
		$this->setOption(self::OPTION_RETURN_HEADERS, $set);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function wantHeaders(): bool {
		return $this->optionBool(self::OPTION_RETURN_HEADERS, true);
	}

	/**
	 * Configure this object to mimic/passthrough the Request
	 *
	 * @param Request $request
	 * @return Net_HTTP_Client
	 */
	public function proxyRequest(Request $request, string $url_prefix): self {
		$this->setMethod($method = $request->method());
		if (in_array($method, [
			'PUT', 'PATCH', 'POST',
		])) {
			$this->setData($request->rawData());
		}
		$this->setURL($url_prefix . $request->uri());
		foreach ($request->headers() as $header => $value) {
			$this->setRequestHeader($header, $value);
		}
		return $this;
	}

	/**
	 * @param Response $response
	 * @return $this
	 */
	public function proxyResponse(Response $response): self {
		$response->setStatus($this->response_code(), $this->response_message());
		$response->setContentType($this->contentType());
		$headers = [];
		foreach ($this->responseHeaders() as $header => $value) {
			if (isset(self::$ignore_response_headers[$header])) {
				continue;
			}
			$headers[] = $header;
			$response->setHeader($header, $value);
		}
		$response->setHeader('X-Debug-Headers', implode(',', $headers));
		$response->setContent($this->content());
		return $this;
	}

	/**
	 * @return array
	 */
	public function requestVariables(): array {
		return [
			'url' => $this->url(), 'requestMethod' => $this->method(), 'requestCookies' => $this->requestCookie,
			'requestData' => $this->data(),
		];
	}

	/**
	 * @return array
	 */
	public function responseVariables(): array {
		return [
			'responseCode' => $this->responseCode, 'responseMessage' => $this->responseMessage,
			'responseProtocol' => $this->responseProtocol, 'responseCodeType' => $this->response_code_type(),
			'responseData' => $this->content,
		];
	}

	/**
	 * @return array
	 */
	public function variables(): array {
		return $this->requestVariables() + $this->responseVariables();
	}
}
