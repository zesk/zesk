<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/import_log/classes/import/log/webserver.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Wed Feb 24 15:47:45 EST 2010 15:47:45
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
abstract class Import_Log_WebServer extends Import_Log {
	const IP = "IP";
	const IP_Integer = "IP_Integer";
	const User = "User";
	const Server_IP = "Server_IP";
	const Server_IP_Integer = "Server_IP_Integer";
	const Server_Port = "Server_Port";
	const Server_Name = "Server_Name";
	const Request_Method = "Request_Method";
	const Request_URI = "Request_URI";
	const Request_URL = "Request_URL";
	const Request_Query_String = "Request_Query_String";
	const Request_Size = "Request_Size";
	const Response_Status = "Response_Status";
	const Response_Size = "Response_Size";
	const Time = "Time";
	const Date = "Date";
	const Duration = "Duration";
	const DateTime = "DateTime";
	const Request_Host = "Request_Host";
	const Request_Version = "Request_Version";
	const Request_User_Agent = "Request_User_Agent";
	const Request_Referrer = "Request_Referrer";
	const Request_Cookie = "Request_Cookie";
	private $_filename;
	private $_fp;
	protected $header_read_size = 1024;
	protected $line_read_size = 10240;
	protected function derive_fields($result) {
		$int_keys = array(
			self::Response_Status,
			self::Response_Size,
			self::Request_Size,
			self::Server_Port
		);
		foreach ($int_keys as $int_key) {
			if (array_key_exists($int_key, $result)) {
				$result[$int_key] = to_integer($result[$int_key], $result[$int_key]);
			}
		}
		if (!arr::has($result, self::DateTime) && arr::has($result, array(
			self::Date,
			self::Time
		))) {
			$result[self::DateTime] = $result[self::Date] . " " . $result[self::Time];
		}
		if (!array_key_exists(self::Request_Host, $result) && $this->has_option(self::Request_Host)) {
			$result[self::Request_Host] = $this->option(self::Request_Host);
		}
		if (!array_key_exists(self::Server_Port, $result)) {
			$result[self::Server_Port] = $this->option(self::Server_Port, 80);
		}
		if (!arr::has($result, self::Request_URL) && arr::has($result, array(
			self::Request_URI,
			self::Server_Port,
			self::Request_Host
		))) {
			$port = $result[self::Server_Port];
			$parts['scheme'] = $this->option_bool("is_secure", ($port === 443)) ? "https" : "http";
			$parts['host'] = $result[self::Request_Host];
			$parts['port'] = $port;
			$parts['path'] = $result[self::Request_URI];
			$result[self::Request_URL] = URL::unparse($parts);
		}
		if (arr::has($result, self::IP) && !arr::has($result, self::IP_Integer)) {
			$result[self::IP_Integer] = inet_pton($result[self::IP]);
		}
		if (arr::has($result, self::IP_Integer) && !arr::has($result, self::IP)) {
			$result[self::IP] = inet_ntop($result[self::IP_Integer]);
		}
		if (arr::has($result, self::Server_IP) && !arr::has($result, self::Server_IP_Integer)) {
			$result[self::Server_IP_Integer] = inet_pton($result[self::Server_IP]);
		}
		if (arr::has($result, self::Server_IP_Integer) && !arr::has($result, self::Server_IP)) {
			$result[self::Server_IP] = inet_ntop($result[self::Server_IP_Integer]);
		}
	}
}
