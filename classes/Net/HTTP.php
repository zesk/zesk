<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

class Net_HTTP {
	// From http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	/*
	 * 100 - carry on
	 */

	/**
	 *
	 * @var integer
	 */
	const STATUS_CONTINUE = 100;
	/**
	 *
	 * @var integer
	 */
	const STATUS_SWITCHING_PROTOCOLS = 101;
	/**
	 *
	 * @var integer
	 */
	const STATUS_PROCESSING = 102;
	/*
	 * 200 - it's all good
	 */
	/**
	 *
	 * @var integer
	 */
	const STATUS_OK = 200;
	/**
	 *
	 * @var integer
	 */
	const STATUS_CREATED = 201;
	/**
	 *
	 * @var integer
	 */
	const STATUS_ACCEPTED = 202;
	/**
	 *
	 * @var integer
	 */
	const STATUS_NON_AUTHORIATIVE_INFORMATION = 203;
	/**
	 *
	 * @var integer
	 */
	const STATUS_NO_CONTENT = 204;
	/**
	 *
	 * @var integer
	 */
	const STATUS_RESET_CONTENT = 205;
	/**
	 *
	 * @var integer
	 */
	const STATUS_PARTIAL_CONTENT = 206;
	/**
	 *
	 * @var integer
	 */
	const STATUS_MULTI_STATUS = 207;

	/*
	 * 300 - Maybe, Maybe not
	 */
	/**
	 * @var integer
	 */
	const STATUS_MULTIPLE_CHOICES = 300;
	/**
	 *
	 * @var integer
	 */
	const STATUS_MOVED_PERMANENTLY = 301;
	/**
	 *
	 * @var integer
	 */
	const STATUS_FOUND = 302;
	/**
	 *
	 * @var integer
	 */
	const STATUS_SEE_OTHER = 303;
	/**
	 *
	 * @var integer
	 */
	const STATUS_NOT_MODIFIED = 304;
	/**
	 *
	 * @var integer
	 */
	const STATUS_USE_PROXY = 305;
	/**
	 *
	 * @var integer
	 */
	const STATUS_TEMPORARY_REDIRECT = 307;

	/*
	 * 400 - Bad client! No biscuit!
	 */
	/**
	 *
	 * @var integer
	 */
	const STATUS_BAD_REQUEST = 400;
	/**
	 *
	 * @var integer
	 */
	const STATUS_UNAUTHORIZED = 401;
	/**
	 *
	 * @var integer
	 */
	const STATUS_PAYMENT_GRANTED = 402;
	/**
	 *
	 * @var integer
	 */
	const STATUS_FORBIDDEN = 403;
	/**
	 *
	 * @var integer
	 */
	const STATUS_FILE_NOT_FOUND = 404;
	/**
	 *
	 * @var integer
	 */
	const STATUS_METHOD_NOT_ALLOWED = 405;
	/**
	 *
	 * @var integer
	 */
	const STATUS_NOT_ACCEPTABLE = 406;
	/**
	 *
	 * @var integer
	 */
	const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
	/**
	 *
	 * @var integer
	 */
	const STATUS_REQUEST_TIMEOUT = 408;
	/**
	 *
	 * @var integer
	 */
	const STATUS_CONFLICT = 409;
	/**
	 *
	 * @var integer
	 */
	const STATUS_GONE = 410;
	/**
	 *
	 * @var integer
	 */
	const STATUS_LENGTH_REQUIRED = 411;
	/**
	 *
	 * @var integer
	 */
	const STATUS_PRECONDITION_FAILED = 412;
	/**
	 *
	 * @var integer
	 */
	const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
	/**
	 *
	 * @var integer
	 */
	const STATUS_REQUEST_URI_TOO_LARGE = 414;
	/**
	 *
	 * @var integer
	 */
	const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
	/**
	 *
	 * @var integer
	 */
	const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	/**
	 *
	 * @var integer
	 */
	const STATUS_EXPECTATION_FAILED = 417;
	/**
	 *
	 * @var integer
	 */
	const STATUS_UNPROCESSABLE_ENTITY = 422;
	/**
	 *
	 * @var integer
	 */
	const STATUS_LOCKED = 423;
	/**
	 *
	 * @var integer
	 */
	const STATUS_FAILED_DEPENDENCY = 424;

	/*
	 * 500 - Bad programmer! No coffee!
	 */
	/**
	 *
	 * @var integer
	 */
	const STATUS_INTERNAL_SERVER_ERROR = 500;
	/**
	 *
	 * @var integer
	 */
	const STATUS_NOT_IMPLEMENTED = 501;
	/**
	 *
	 * @var integer
	 */
	const STATUS_OVERLOADED = 502;
	/**
	 *
	 * @var integer
	 */
	const STATUS_GATEWAY_TIMEOUT = 503;
	/**
	 *
	 * @var integer
	 */
	const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
	/**
	 *
	 * @var integer
	 */
	const STATUS_INSUFFICIENT_STORAGE = 507;

	/*
	 * Response Type - intval(STATUS_FOO / 100)
	 */
	/**
	 *
	 * @var integer
	 */
	const RESPONSE_TYPE_INFO = 1;
	/**
	 *
	 * @var integer
	 */
	const RESPONSE_TYPE_SUCCESS = 2;
	/**
	 *
	 * @var integer
	 */
	const RESPONSE_TYPE_REDIRECT = 3;
	/**
	 *
	 * @var integer
	 */
	const RESPONSE_TYPE_ERROR_CLIENT = 4;
	/**
	 *
	 * @var integer
	 */
	const RESPONSE_TYPE_ERROR_SERVER = 5;

	/* Method types */
	/**
	 *
	 * @var string
	 */
	const METHOD_GET = "GET";
	/**
	 *
	 * @var string
	 */
	const METHOD_POST = "POST";
	/**
	 *
	 * @var string
	 */
	const METHOD_PUT = "PUT";
	/**
	 * DELETE a resource
	 *
	 * @var string
	 */
	const METHOD_DELETE = "DELETE";
	/**
	 * Just the header, no content expected
	 *
	 * @var string
	 */
	const METHOD_HEAD = "HEAD";
	/**
	 *
	 * @var string
	 */
	const METHOD_OPTIONS = "OPTIONS";
	/**
	 *
	 * @var string
	 */
	const METHOD_TRACE = "TRACE";
	/**
	 *
	 * @var string
	 */
	const METHOD_CONNECT = "CONNECT";
	/**
	 * PROPFIND — used to retrieve properties, stored as XML, from a web resource. It is also overloaded to allow one to retrieve the collection structure (a.k.a. directory hierarchy) of a remote system.
	 *
	 * @var string
	 */
	const METHOD_PROPFIND = "PROPFIND";

	/* Request headers */
	/**
	 *
	 * @var string
	 */
	const REQUEST_REFERRER = "Referer";
	/**
	 *
	 * @var string
	 */
	const REQUEST_USER_AGENT = "User-Agent";
	/**
	 *
	 * @var string
	 */
	const REQUEST_ACCEPT = "Accept";
	/**
	 *
	 * @var string
	 */
	const REQUEST_CONTENT_TYPE = "Content-Type";

	/* Response headers */
	/**
	 *
	 * @var string
	 */
	const RESPONSE_CONTENT_DISPOSITION = "Content-Disposition";
	/**
	 *
	 * @var string
	 */
	const RESPONSE_CONTENT_TYPE = "Content-Type";
	/**
	 *
	 * @var string
	 */
	const RESPONSE_ACCEPT_RANGES = "Accept-Ranges";
	/**
	 *
	 * @var string
	 */
	const RESPONSE_CONTENT_ENCODING = "Content-Encoding";
	/**
	 *
	 * @var string
	 */
	const RESPONSE_TRANSFER_ENCODING = "Transfer-Encoding";

	/**
	 *
	 * @var string
	 */
	const HEADER_CONTENT_LENGTH = "Content-Length";

	/**
	 * Valid methods. 2018-05 added PROPFIND
	 *
	 * @var array
	 */
	static $methods = array(
		self::METHOD_OPTIONS => self::METHOD_OPTIONS,
		self::METHOD_GET => self::METHOD_GET,
		self::METHOD_POST => self::METHOD_POST,
		self::METHOD_PUT => self::METHOD_PUT,
		self::METHOD_DELETE => self::METHOD_DELETE,
		self::METHOD_HEAD => self::METHOD_HEAD,
		self::METHOD_TRACE => self::METHOD_TRACE,
		self::METHOD_CONNECT => self::METHOD_CONNECT,
		self::METHOD_PROPFIND => self::METHOD_PROPFIND
	);

	/**
	 * Default status text when custom status is not given
	 *
	 * @var array
	 */
	static $status_text = array(
		self::STATUS_CONTINUE => 'Continue',
		self::STATUS_SWITCHING_PROTOCOLS => 'Switching Protocols',
		self::STATUS_PROCESSING => 'Processing',
		self::STATUS_OK => 'OK',
		self::STATUS_CREATED => 'Created',
		self::STATUS_ACCEPTED => 'Accepted',
		self::STATUS_NON_AUTHORIATIVE_INFORMATION => 'Non Authoriative Information',
		self::STATUS_NO_CONTENT => 'No Content',
		self::STATUS_RESET_CONTENT => 'Reset Content',
		self::STATUS_PARTIAL_CONTENT => 'Partial Content',
		self::STATUS_MULTI_STATUS => 'Multi Status',
		self::STATUS_MULTIPLE_CHOICES => 'Multiple Choices',
		self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
		self::STATUS_FOUND => 'Found',
		self::STATUS_SEE_OTHER => 'See Other',
		self::STATUS_NOT_MODIFIED => 'Not Modified',
		self::STATUS_USE_PROXY => 'Use Proxy',
		self::STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
		self::STATUS_BAD_REQUEST => 'Bad Request',
		self::STATUS_UNAUTHORIZED => 'Unauthorized',
		self::STATUS_PAYMENT_GRANTED => 'Payment Granted',
		self::STATUS_FORBIDDEN => 'Forbidden',
		self::STATUS_FILE_NOT_FOUND => 'File Not Found',
		self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
		self::STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
		self::STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
		self::STATUS_REQUEST_TIMEOUT => 'Request Time out',
		self::STATUS_CONFLICT => 'Conflict',
		self::STATUS_GONE => 'Gone',
		self::STATUS_LENGTH_REQUIRED => 'Length Required',
		self::STATUS_PRECONDITION_FAILED => 'Precondition Failed',
		self::STATUS_REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
		self::STATUS_REQUEST_URI_TOO_LARGE => 'Request-URI Too Large',
		self::STATUS_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
		self::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested range not satisfiable',
		self::STATUS_EXPECTATION_FAILED => 'Expectation Failed',
		self::STATUS_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
		self::STATUS_LOCKED => 'Locked',
		self::STATUS_FAILED_DEPENDENCY => 'Failed Dependency',
		self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
		self::STATUS_NOT_IMPLEMENTED => 'Not Implemented',
		self::STATUS_OVERLOADED => 'Overloaded',
		self::STATUS_GATEWAY_TIMEOUT => 'Gateway Timeout',
		self::STATUS_HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version not supported',
		self::STATUS_INSUFFICIENT_STORAGE => 'Insufficient Storage'
	);

	/**
	 *
	 * @var array
	 */
	static $request_headers = array(
		"accept" => self::REQUEST_ACCEPT,
		"accept-charset" => "Accept-Charset",
		"accept-encoding" => "Accept-Encoding",
		"accept-language" => "Accept-Language",
		"accept-datetime" => "Accept-Datetime",
		"authorization" => "Authorization",
		"cache-control" => "Cache-Control",
		"connection" => "Connection",
		"cookie" => "Cookie",
		"content-length" => self::HEADER_CONTENT_LENGTH,
		"content-md5" => "Content-MD5",
		"content-type" => self::REQUEST_CONTENT_TYPE,
		"date" => "Date",
		"expect" => "Expect",
		"from" => "From",
		"host" => "Host",
		"if-match" => "If-Match",
		"if-modified-since" => "If-Modified-Since",
		"if-none-match" => "If-None-Match",
		"if-range" => "If-Range",
		"if-unmodified-since" => "If-Unmodified-Since",
		"max-forwards" => "Max-Forwards",
		"origin" => "Origin",
		"pragma" => "Pragma",
		"proxy-authorization" => "Proxy-Authorization",
		"range" => "Range",
		"referer" => self::REQUEST_REFERRER,
		"referrer" => self::REQUEST_REFERRER,
		"te" => "TE",
		"user-agent" => self::REQUEST_USER_AGENT,
		"upgrade" => "Upgrade",
		"via" => "Via",
		"warning" => "Warning",

		/* Non-standard */
		"x-requested-with" => "X-Requested-With",
		"dnt" => "DNT",
		"x-forwarded-for" => "X-Forwarded-For",
		"x-forwarded-host" => "X-Forwarded-Host",
		"x-forwarded-proto" => "X-Forwarded-Proto",
		"front-end-https" => "Front-End-Https",
		"x-http-method-override" => "X-Http-Method-Override",
		"x-att-deviceid" => "X-ATT-DeviceId",
		"x-wap-profile" => "X-Wap-Profile",
		"proxy-connection" => "Proxy-Connection",
		"x-uidh" => "X-UIDH",
		"x-csrf-token" => "X-Csrf-Token"
	);

	/**
	 * https://en.wikipedia.org/wiki/List_of_HTTP_header_fields
	 *
	 * @var array
	 */
	static $response_headers = array(
		"access-control-allow-origin" => "Access-Control-Allow-Origin",
		"accept-patch" => "Accept-Patch",
		"accept-ranges" => self::RESPONSE_ACCEPT_RANGES,
		"age" => "Age",
		"allow" => "Allow",
		"cache-control" => "Cache-Control",
		"connection" => "Connection",
		"content-disposition" => self::RESPONSE_CONTENT_DISPOSITION,
		"content-encoding" => self::RESPONSE_CONTENT_ENCODING,
		"content-language" => "Content-Language",
		"content-length" => self::HEADER_CONTENT_LENGTH,
		"content-location" => "Content-Location",
		"content-md5" => "Content-MD5",
		"content-range" => "Content-Range",
		"content-type" => "Content-Type",
		"date" => "Date",
		"etag" => "ETag",
		"expires" => "Expires",
		"last-modified" => "Last-Modified",
		"link" => "Link",
		"location" => "Location",
		"p3p" => "P3P",
		"pragma" => "Pragma",
		"proxy-authenticate" => "Proxy-Authenticate",
		"public-key-pins" => "Public-Key-Pins",
		"refresh" => "Refresh",
		"retry-after" => "Retry-After",
		"permanent" => "Permanent",
		"server" => "Server",
		"set-cookie" => "Set-Cookie",
		"status" => "Status",
		"status-line" => "Status-Line",
		"strict-transport-security" => "Strict-Transport-Security",
		"trailer" => "Trailer",
		"transfer-encoding" => self::RESPONSE_TRANSFER_ENCODING,
		"upgrade" => "Upgrade",
		"vary" => "Vary",
		"via" => "Via",
		"warning" => "Warning",
		"www-authenticate" => "WWW-Authenticate",

		/* Non-standard */
		"x-xss-protection" => "X-XSS-Protection",
		"content-security-policy" => "Content-Security-Policy",
		"x-content-security-policy" => "X-Content-Security-Policy",
		"x-webkit-csp" => "X-WebKit-CSP",
		"x-content-type-options" => "X-Content-Type-Options",
		"x-powered-by" => "X-Powered-By",
		"x-ua-compatible" => "X-UA-Compatible",
		"x-content-duration" => "X-Content-Duration"
	);
}
