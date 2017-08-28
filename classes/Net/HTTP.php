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
	const Status_Continue = 100;
	const Status_Switching_Protocols = 101;
	const Status_Processing = 102;
	const Status_OK = 200;
	const Status_Created = 201;
	const Status_Accepted = 202;
	const Status_Non_Authoriative_Information = 203;
	const Status_No_Content = 204;
	const Status_Reset_Content = 205;
	const Status_Partial_Content = 206;
	const Status_Multi_Status = 207;
	const Status_Multiple_Choices = 300;
	const Status_Moved_Permanently = 301;
	const Status_Found = 302;
	const Status_See_Other = 303;
	const Status_Not_Modified = 304;
	const Status_Use_Proxy = 305;
	const Status_Temporary_Redirect = 307;
	const Status_Bad_Request = 400;
	const Status_Unauthorized = 401;
	const Status_Payment_Granted = 402;
	const Status_Forbidden = 403;
	const Status_File_Not_Found = 404;
	const Status_Method_Not_Allowed = 405;
	const Status_Not_Acceptable = 406;
	const Status_Proxy_Authentication_Required = 407;
	const Status_Request_Time_out = 408;
	const Status_Conflict = 409;
	const Status_Gone = 410;
	const Status_Length_Required = 411;
	const Status_Precondition_Failed = 412;
	const Status_Request_Entity_Too_Large = 413;
	const Status_Request_URI_Too_Large = 414;
	const Status_Unsupported_Media_Type = 415;
	const Status_Requested_range_not_satisfiable = 416;
	const Status_Expectation_Failed = 417;
	const Status_Unprocessable_Entity = 422;
	const Status_Locked = 423;
	const Status_Failed_Dependency = 424;
	const Status_Internal_Server_Error = 500;
	const Status_Not_Implemented = 501;
	const Status_Overloaded = 502;
	const Status_Gateway_Timeout = 503;
	const Status_HTTP_Version_not_supported = 505;
	const Status_Insufficient_Storage = 507;
	const response_type_info = 1;
	const response_type_success = 2;
	const response_type_redirect = 3;
	const response_type_error_client = 4;
	const response_type_error_server = 5;
	const Method_GET = "GET";
	const Method_POST = "POST";
	const Method_PUT = "PUT";
	const Method_DELETE = "DELETE";
	const Method_HEAD = "HEAD";
	const Method_OPTIONS = "OPTIONS";
	const Method_TRACE = "TRACE";
	const Method_CONNECT = "CONNECT";
	const request_Referrer = "Referer";
	const request_User_Agent = "User-Agent";
	const request_Accept = "Accept";
	const request_Content_Type = "Content-Type";
	const response_Content_Disposition = "Content-Disposition";
	const response_Content_Type = "Content-Type";
	const response_Accept_Ranges = "Accept-Ranges";
	const response_Content_Encoding = "Content-Encoding";
	const response_Transfer_Encoding = "Transfer-Encoding";
	static $methods = array(
		self::Method_OPTIONS => self::Method_OPTIONS,
		self::Method_GET => self::Method_GET,
		self::Method_POST => self::Method_POST,
		self::Method_PUT => self::Method_PUT,
		self::Method_DELETE => self::Method_DELETE,
		self::Method_HEAD => self::Method_HEAD,
		self::Method_TRACE => self::Method_TRACE,
		self::Method_CONNECT => self::Method_CONNECT
	);
	static $status_text = array(
		self::Status_Continue => 'Continue',
		self::Status_Switching_Protocols => 'Switching Protocols',
		self::Status_Processing => 'Processing',
		self::Status_OK => 'OK',
		self::Status_Created => 'Created',
		self::Status_Accepted => 'Accepted',
		self::Status_Non_Authoriative_Information => 'Non Authoriative Information',
		self::Status_No_Content => 'No Content',
		self::Status_Reset_Content => 'Reset Content',
		self::Status_Partial_Content => 'Partial Content',
		self::Status_Multi_Status => 'Multi Status',
		self::Status_Multiple_Choices => 'Multiple Choices',
		self::Status_Moved_Permanently => 'Moved Permanently',
		self::Status_Found => 'Found',
		self::Status_See_Other => 'See Other',
		self::Status_Not_Modified => 'Not Modified',
		self::Status_Use_Proxy => 'Use Proxy',
		self::Status_Temporary_Redirect => 'Temporary Redirect',
		self::Status_Bad_Request => 'Bad Request',
		self::Status_Unauthorized => 'Unauthorized',
		self::Status_Payment_Granted => 'Payment Granted',
		self::Status_Forbidden => 'Forbidden',
		self::Status_File_Not_Found => 'File Not Found',
		self::Status_Method_Not_Allowed => 'Method Not Allowed',
		self::Status_Not_Acceptable => 'Not Acceptable',
		self::Status_Proxy_Authentication_Required => 'Proxy Authentication Required',
		self::Status_Request_Time_out => 'Request Time out',
		self::Status_Conflict => 'Conflict',
		self::Status_Gone => 'Gone',
		self::Status_Length_Required => 'Length Required',
		self::Status_Precondition_Failed => 'Precondition Failed',
		self::Status_Request_Entity_Too_Large => 'Request Entity Too Large',
		self::Status_Request_URI_Too_Large => 'Request-URI Too Large',
		self::Status_Unsupported_Media_Type => 'Unsupported Media Type',
		self::Status_Requested_range_not_satisfiable => 'Requested range not satisfiable',
		self::Status_Expectation_Failed => 'Expectation Failed',
		self::Status_Unprocessable_Entity => 'Unprocessable Entity',
		self::Status_Locked => 'Locked',
		self::Status_Failed_Dependency => 'Failed Dependency',
		self::Status_Internal_Server_Error => 'Internal Server Error',
		self::Status_Not_Implemented => 'Not Implemented',
		self::Status_Overloaded => 'Overloaded',
		self::Status_Gateway_Timeout => 'Gateway Timeout',
		self::Status_HTTP_Version_not_supported => 'HTTP Version not supported',
		self::Status_Insufficient_Storage => 'Insufficient Storage'
	);
	const header_content_length = "Content-Length";
	static $request_headers = array(
		"accept" => self::request_Accept,
		"accept-charset" => "Accept-Charset",
		"accept-encoding" => "Accept-Encoding",
		"accept-language" => "Accept-Language",
		"accept-datetime" => "Accept-Datetime",
		"authorization" => "Authorization",
		"cache-control" => "Cache-Control",
		"connection" => "Connection",
		"cookie" => "Cookie",
		"content-length" => self::header_content_length,
		"content-md5" => "Content-MD5",
		"content-type" => self::request_Content_Type,
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
		"referer" => self::request_Referrer,
		"referrer" => self::request_Referrer,
		"te" => "TE",
		"user-agent" => self::request_User_Agent,
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
		"accept-ranges" => self::response_Accept_Ranges,
		"age" => "Age",
		"allow" => "Allow",
		"cache-control" => "Cache-Control",
		"connection" => "Connection",
		"content-disposition" => self::response_Content_Disposition,
		"content-encoding" => self::response_Content_Encoding,
		"content-language" => "Content-Language",
		"content-length" => "Content-Length",
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
		"transfer-encoding" => self::response_Transfer_Encoding,
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
