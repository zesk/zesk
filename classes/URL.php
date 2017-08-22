<?php

/**
 *
 */
namespace zesk;

/**
 * Difference between zesk\URL and \URL:: is that we can do new zesk\URL($url) (TODO)
 */
class URL {
	static $url_ordering = array(
		'scheme',
		'user',
		'pass',
		'host',
		'port',
		'path',
		'query',
		'fragment'
	);

	/**
	 * Query string parsing (case-sensitive)
	 *
	 * @param string $qs
	 *        	Query string to parse (Does NOT parse URLs)
	 * @param string $name
	 *        	Name of field to return, or null to return an array
	 * @param string $default
	 *        	Value to return if name not found in query string
	 * @return mixed Parsed query string
	 */
	static function query_parse($qs, $name = null, $default = null) {
		$res = array();
		parse_str($qs, $res);
		if (is_array($res) && is_string($name)) {
			return avalue($res, $name, $default);
		}
		return $res;
	}

	/**
	 * Case-insensitive query string parsing
	 *
	 * @param string $qs
	 *        	Query string to parse (Does NOT parse URLs)
	 * @param string $name
	 *        	Name of field to return, or null to return an array
	 * @param string $default
	 *        	Value to return if name not found in query string
	 * @return mixed Parsed query string
	 */
	static function query_iparse($qs, $name = null, $default = null) {
		$res = array();
		parse_str($qs, $res);
		$res = array_change_key_case($res);
		if ($name === null) {
			return $res;
		}
		return avalue($res, $name, $default);
	}

	/**
	 * Similar to parse_str.
	 * Returns false if the query string or URL is empty. Because we're not parsing to
	 * variables but to array key entries, this function will handle ?[]=1&[]=2 "correctly."
	 *
	 * Use this instead? Issues with . in variables names for parse_str may mean: use this instead
	 *
	 * @return array Similar to the $_GET formatting that PHP does automagically.
	 * @param string $url
	 *        	A query string or URL
	 * @param boolean $qmark
	 *        	Find and strip out everything before the question mark in the string
	 * @param boolean $simple
	 *        	Do not parse PHP arrays, just create values for them
	 */
	public static function query_parse_url($url, $qmark = true, $simple = false) {
		if ($qmark) {
			$url = str::right($url, "?");
			$url = str::left($url, "#");
		}
		if (empty($url)) {
			return array();
		}
		$tokens = explode("&", $url);
		$urlVars = array();
		foreach ($tokens as $token) {
			list($token, $value) = pair($token, "=", $token, "");
			$matches = false;
			if (!$simple && preg_match('/^([^\[]*)(\[.*\])$/', $token, $matches)) {
				self::_query_parse_array($urlVars, $matches[1], $matches[2], $value);
			} else {
				$urlVars[urldecode($token)] = urldecode($value);
			}
		}
		return $urlVars;
	}

	/**
	 * Utility function for URL::query_parse.
	 * Given a result array, a starting key, and a set of keys formatted like "[a][b][c]"
	 * and the final value, updates the result array with the correct PHP array keys.
	 *
	 * @return void
	 * @param array $result
	 *        	A result array to populate from the query string
	 * @param string $k
	 *        	The starting key to populate in $result
	 * @param string $arrayKeys
	 *        	The key list to parse in the form "[][a][what%20ever]"
	 * @param string $value
	 *        	The value to place at the destination array key
	 */
	private static function _query_parse_array(&$result, $k, $arrayKeys, $value) {
		$matches = false;
		if (!preg_match_all('/\[([^\]]*)\]/', $arrayKeys, $matches)) {
			return;
		}
		if (!isset($result[$k])) {
			$result[urldecode($k)] = array();
		}
		$temp = & $result[$k];
		$last = urldecode(array_pop($matches[1]));
		foreach ($matches[1] as $k) {
			$k = urldecode($k);
			if ($k === "") {
				$temp[] = array();
				$temp = & $temp[count($temp) - 1];
			} else if (!isset($temp[$k])) {
				$temp[$k] = array();
				$temp = & $temp[$k];
			}
		}
		if ($last === "") {
			$temp[] = $value;
		} else {
			$temp[urldecode($last)] = $value;
		}
	}
	static function query_from_mixed($url, $lower = true) {
		if (is_array($url)) {
			return $lower ? array_change_key_case($url) : $url;
		}
		$query_string = $url;
		if (self::is($url)) {
			$u = self::parse($url);
			if ($u === false) {
				return false;
			}
			$query_string = avalue($u, "query");
		}
		if (empty($query_string)) {
			return false;
		}
		$qs = self::query_parse($query_string);
		if (!is_array($qs)) {
			return false;
		}
		if (count($qs) === 0) {
			return false;
		}
		if (!$lower) {
			return $qs;
		}
		$qs = array_change_key_case($qs);
		return $qs;
	}
	static function query_format($path, $add = null, $remove = null) {
		list($uri, $qs) = pair($path, "?", $path, null);
		if ($qs === null) {
			$qs = array();
		} else {
			$qs = self::query_parse($qs);
		}
		$remove = to_list($remove, array());
		foreach ($remove as $k) {
			unset($qs[$k]);
		}
		if (is_array($add)) {
			$qs = arr::merge($qs, $add);
		}
		return $uri . self::query_unparse($qs);
	}
	static private function query_unparse_arr($key_name, $qs, $sep = "&") {
		$item = array();
		foreach ($qs as $k => $v) {
			$name = urldecode($key_name) . '[' . urlencode($k) . ']';
			if (is_array($v)) {
				$item[] = self::query_unparse_arr($name, $v);
			} else {
				$item[] = $name . '=' . urlencode($v);
			}
		}
		return implode($sep, $item);
	}
	static function query_unparse(array $qs, $include = null) {
		if (count($qs) === 0) {
			return "";
		}
		$item = array();
		foreach ($qs as $k => $v) {
			if ($include !== null && !Lists::contains($include, $k)) {
				continue;
			}
			if (is_array($v)) {
				$item[] = self::query_unparse_arr($k, $v);
			} else {
				$item[] = urlencode($k) . '=' . urlencode($v);
			}
		}
		return "?" . implode("&", $item);
	}
	/**
	 * Append to the query string of a URL
	 *
	 * @param string $u
	 * @param mixed $values
	 *        	Array or string to append
	 * @param boolean $is_href
	 *        	Deprecated. Do not use.
	 * @return string
	 */
	public static function query_append($u, $values = null) {
		$amp = "&";
		if (is_array($values)) {
			$qs_append = array();
			foreach ($values as $k => $v) {
				if (is_array($v)) {
					$qs_append[] = self::query_unparse_arr($k, $v);
				} else {
					$qs_append[] = urlencode($k) . '=' . urlencode($v);
				}
			}
			$qs_append = implode($amp, $qs_append);
		} else {
			$qs_append = $values;
		}
		if (strval($qs_append) === "") {
			return $u;
		}
		$sep = (strpos($u, "?") === false) ? "?" : $amp;
		return $u . $sep . $qs_append;
	}
	public static function query_remove($u, $names, $isHREF = false) {
		list($u, $m) = pair($u, "#", $u, null);
		$x = strpos($u, "?");
		if ($x === false) {
			return $u;
		}
		$q = substr($u, $x + 1);
		$newu = substr($u, 0, $x);
		$q = explode("&", $q);
		$nq = array();
		foreach ($q as $i) {
			$kv = explode("=", $i, 2);
			if (!Lists::contains($names, $kv[0])) {
				$nq[] = $i;
			}
		}
		$m = ($m ? "#$m" : "");
		if (count($nq) == 0) {
			return $newu . $m;
		}
		return $newu . "?" . implode($isHREF ? "&amp;" : "&", $nq) . $m;
	}
	public static function query_iremove($u, $names) {
		list($x, $m) = pair($u, "#", $u, null);
		$m = ($m ? "#$m" : "");
		$x = strpos($u, "?");
		if ($x === false) {
			return $u;
		}
		$q = substr($u, $x + 1);
		$newu = substr($u, 0, $x);
		$qs = array();
		parse_str($q, $qs);
		$names = arr::change_value_case($names);
		foreach ($qs as $k => $v) {
			if (in_array(strtolower($k), $names)) {
				unset($qs[$k]);
				if (count($qs) === 0) {
					break;
				}
			}
		}
		if (count($qs) === 0) {
			return $newu . $m;
		}
		$nq = array();
		foreach ($qs as $k => $v) {
			$nq[] = "$k=" . urlencode($v);
		}
		return $newu . "?" . implode("&", $nq) . $m;
	}

	/**
	 * Does this URL have a reference parameter already?
	 *
	 * @param string $href
	 * @param string $key
	 * @return boolean
	 */
	public static function has_ref($href, $key = "ref") {
		return self::query_parse(str::right($href, "?", ""), $key) !== null;
	}
	public static function add_ref($href, $ref = null, $key = "ref") {
		if ($ref) {
			if (substr($ref, 0, 1) === '#') {
				$ref = self::current_uri() . $ref;
			}
		} else {
			$ref = self::current_uri();
		}
		return self::query_append(self::query_remove($href, $key), array(
			$key => $ref
		));
	}
	public static function current_scheme() {
		// Amazon load balancers
		if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER)) {
			return $_SERVER['HTTP_X_FORWARDED_PROTO'];
		}
		return avalue($_SERVER, 'HTTPS') === "on" ? "https" : "http";
	}
	public static function current_host() {
		$host = avalue($_SERVER, 'HTTP_HOST', "localhost");
		return strtolower(str::left($host, ":", $host));
	}
	public static function current_port() {
		return intval(avalue($_SERVER, 'HTTP_X_FORWARDED_PORT', avalue($_SERVER, 'SERVER_PORT', 80)));
	}
	public static function current_uri() {
		return avalue($_SERVER, 'REQUEST_URI');
	}
	public static function current_query_remove($names) {
		return self::query_remove(self::current_uri(), $names);
	}
	public static function current_path() {
		$uri = self::current_uri();
		return str::left($uri, "?", $uri);
	}
	public static function current() {
		$parts['scheme'] = self::current_scheme();
		$parts['host'] = self::current_host();
		$parts['port'] = self::current_port();
		$parts['path'] = self::current_path();
		$parts['query'] = avalue($_SERVER, 'QUERY_STRING', '');
		return self::unparse($parts);
	}

	/**
	 *
	 * @deprecated 2017-03
	 * @param unknown $part
	 * @return string
	 */
	public static function current_left($part) {
		return self::left(self::current(), $part);
	}
	/**
	 *
	 * @deprecated 2017-03
	 *
	 * @return string
	 */
	public static function current_left_host() {
		return self::left_host(self::current());
	}
	/**
	 *
	 * @deprecated 2017-03
	 * @return string
	 */
	public static function current_left_path() {
		return self::left(self::current(), 'path');
	}

	/**
	 *
	 * @deprecated 2017-03
	 * @return boolean
	 */
	public static function current_is_secure() {
		return self::is_secure(self::current());
	}

	/**
	 * Parse a URL without choking on non-URLs
	 *
	 * @param mixed $url
	 *        	URL to parse
	 * @param string $component
	 *        	Optional string of component to retrieve. Different than parse_url as it doesn't
	 *        	take the constant, just the string component
	 * @param string $default
	 *        	Default value to return if component specified is not found
	 * @return mixed An associative array if whole URL is parsed, a string if component, or false if
	 *         parsing failed
	 */
	public static function parse($url, $component = null, $default = null) {
		$url = trim($url);
		if (preg_match('%^[a-z][a-z0-9]*://.+|^mailto:.+@.+%', strtolower($url)) === 0) {
			return false;
		}
		$result = array();
		if (strtolower(substr($url, 0, 7)) === "file://") {
			$result = array(
				"scheme" => "file",
				"host" => "",
				"path" => substr($url, 7)
			);
		} else {
			$result = @parse_url($url);
		}
		if (!is_array($result)) {
			return $result;
		}
		foreach ($result as $k => $v) {
			$result[$k] = urldecode($v);
		}
		if (avalue($result, 'scheme') === 'mailto' && array_key_exists('path', $result)) {
			$path = $result['path'];
			unset($result['path']);
			list($user, $host) = pair($path, '@', null, $path);
			if ($user) {
				$result['user'] = $user;
			}
			if ($host) {
				$result['host'] = $host;
			}
		}
		if ($component === null) {
			return $result;
		}
		return avalue($result, $component, $default);
	}

	/**
	 * Takes a URL or url parts (array) and adds useful variables helpful in generating URLs as
	 * variables
	 *
	 * @param string $url
	 * @return array
	 */
	public static function variables($url) {
		if (is_array($url)) {
			$url = self::unparse($url);
		}
		$parts = self::parse($url);
		$parts['url'] = $url;
		if (array_key_exists('host', $parts)) {
			$parts['host:port'] = $parts['host'] . (array_key_exists('port', $parts) ? ":" . $parts['port'] : "");
		}
		if (array_key_exists('scheme', $parts)) {
			$parts['scheme:host:port'] = self::left($url, 'host');
		}
		return $parts;
	}
	public static function unparse($parts) {
		if (!is_array($parts) || !array_key_exists("scheme", $parts)) {
			return null;
		}
		$scheme = strtolower($parts["scheme"]);
		$url = $scheme . (($scheme === "mailto") ? ":" : "://");

		$temp = avalue($parts, "user");
		if ($temp !== null) {
			$url .= urlencode($temp);

			$temp = avalue($parts, "pass");
			if ($temp !== null) {
				$url .= ":" . urlencode($parts["pass"]);
			}

			$url .= "@";
		}

		$url .= strtolower(avalue($parts, "host"));

		$temp = intval(avalue($parts, "port"));
		if ($temp && ($temp !== self::protocol_default_port($scheme))) {
			$url .= ":" . $parts["port"];
		}

		$temp = avalue($parts, "path");
		if (!empty($temp)) {
			if ($temp[0] !== '/') {
				$temp = "/$temp";
			}
			$url .= $temp;
		} else {
			$url .= "/";
		}
		$temp = avalue($parts, "query");
		if ($temp) {
			$url .= "?" . $temp;
		}

		$temp = avalue($parts, "fragment");
		if ($temp) {
			$url .= "#" . urlencode($temp);
		}

		return $url;
	}
	public static function valid($url) {
		return self::is($url);
	}
	public static function is($url) {
		$p = self::parse($url);
		if (!is_array($p)) {
			return false;
		}
		$s = avalue($p, "scheme");
		if ($s !== "http" && $s !== "https") {
			return true;
		}
		$a = strtolower(avalue($p, "host", ""));
		if (preg_match("/[^-a-z.0-9]/", $a)) {
			return false;
		}
		return true;
	}
	public static function remove_password($x) {
		$parts = parse_url($x);
		unset($parts['pass']);
		return self::unparse($parts);
	}
	public static function protocol_default_port($x) {
		static $protocols = array(
			"ftp" => 21,
			"mailto" => 25,
			"http" => 80,
			"https" => 443,
			"file" => false
		);
		return avalue($protocols, strtolower($x), false);
	}

	/**
	 * Converts a URL into its normalized form for comparison.
	 * The following is done:
	 * <ul>
	 * <li>The scheme is made lowercase</li>
	 * <li>The host is made lowercase</li>
	 * <li>For urls with a path, the minimum path of '/' is added</li>
	 * <li>If the port specified is the default port, it is removed from the URL</li>
	 * </ul>
	 *
	 * @param string $u
	 *        	A URL to parse
	 * @return string The normalized URL, or false if the URL is not valid
	 */
	public static function normalize($u) {
		$p = self::parse($u);
		if (!is_array($p)) {
			return false;
		}
		$proto = strtolower(avalue($p, 'scheme'));
		if (empty($proto)) {
			return false;
		}
		$proto = strtolower($proto);

		$host = avalue($p, 'host');
		if (!empty($host)) {
			$p['host'] = trim(strtolower(urldecode($host)));
		}

		$p['scheme'] = $proto;
		if ($proto !== "mailto") {
			$p['path'] = avalue($p, 'path', '/');
		}

		return self::unparse($p);
	}

	/**
	 * Return the left-hand portion of the URL up to the specified part
	 *
	 * e.g.
	 * <code>
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'query') ===
	 * 'http://www.example.com:80/path/to?query=1'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'port') ===
	 * 'http://www.example.com:80/'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'host') ===
	 * 'http://www.example.com/'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'sceheme') ===
	 * 'http://www.example.com/'
	 *
	 * @param string $url
	 *        	URL to trim
	 * @param string $part
	 *        	Return the left part of the URL up to and including this portion
	 * @return string
	 */
	public static function left($url, $part) {
		$parts = self::parse($url);
		if (!is_array($parts)) {
			return false;
		}
		$new_parts = array();
		foreach (self::$url_ordering as $part_item) {
			if (array_key_exists($part_item, $parts)) {
				$new_parts[$part_item] = $parts[$part_item];
			}
			if ($part_item === $part) {
				break;
			}
		}
		return self::unparse($new_parts);
	}

	/**
	 * Returns everything in the URL prior to the path, query string, and fragment
	 * e.g.
	 * <pre>http://www.example.com:98/path/index.php?id=323&o=123#top</pre>
	 * becomes
	 * <pre>http://www.example.com:98/</pre>
	 * This is useful to return an absolute path of a resource at the same address.
	 *
	 * @param string $u
	 *        	A url to modify
	 * @return string The URL up to the path
	 */
	public static function left_host($u) {
		return self::left($u, 'port');
	}

	/**
	 * Returns everything in the URL prior to the query string, and fragment
	 * e.g.
	 * <pre>http://www.example.com:98/path/index.php?id=323&o=123#top</pre>
	 * becomes
	 * <pre>http://www.example.com:98/path/index.php</pre>
	 * This is useful to return an absolute path of a resource at the same address.
	 *
	 * @param string $u
	 *        	A url to modify
	 * @return string The URL up to the path
	 */
	public static function left_path($u) {
		return self::left($u, 'path');
	}

	/**
	 * Fix common issues with URL formatting, particularly when passed in via query strings, etc.
	 *
	 * @param string $u
	 *        	A potentially malformed URL
	 * @param string $default
	 *        	Value to return if repair fails
	 * @return string Repaired URL, or false if repair failed
	 */
	public static function repair($u, $default = false) {
		if (strpos($u, 'https%3A//') === 0 || strpos($u, 'http%3A//') === 0) {
			$u = urldecode($u);
		} else if (preg_match('|^https?:/[^/]|', $u)) {
			$u = preg_replace('|^http(s)?:/|', 'http$1://', $u);
		}
		$parts = self::parse($u);
		if (!is_array($parts)) {
			return $default;
		}
		if (array_key_exists('host', $parts)) {
			$parts['host'] = strtolower(preg_replace('/[^A-Za-z0-9.-]/', '', $parts['host']));
			$u = self::unparse($parts);
		}
		if (!self::is($u)) {
			return $default;
		}
		return $u;
	}

	/**
	 * Returns the scheme of the url
	 *
	 * @param string $url
	 *        	A url to extract the scheme from
	 * @param mixed $default
	 *        	The return value upon failure
	 * @return string The scheme, or $default if url is invalid
	 */
	public static function scheme($url, $default = false) {
		$result = self::parse($url, 'scheme');
		return ($result === false) ? $default : strtolower($result);
	}

	/**
	 * Returns the host of a URL)
	 *
	 * @param string $url
	 *        	The url to extract the host information from
	 * @param mixed $default
	 *        	The return value upon failure
	 * @return string The host in the URL, or $default if url is invalid or doesn't have a host
	 */
	public static function host($url, $default = false) {
		$result = self::parse($url, 'host');
		return ($result === false) ? $default : $result;
	}

	/**
	 * Returns the query string of a URL (a=b&c=d)
	 *
	 * @param string $url
	 *        	The url to extract the host information from
	 * @param mixed $default
	 *        	The return value upon failure
	 * @return string The host in the URL, or $default if url is invalid or doesn't have a host
	 */
	public static function query($url, $default = false) {
		if ($url === null) {
			return $default;
		}
		return self::parse($url, 'query');
	}

	/**
	 * Returns the path of a URL (/path/to/index.php)
	 *
	 * @param string $url
	 *        	The url to extract the path information from
	 * @param mixed $default
	 *        	The return value upon failure
	 * @return string The path in the URL, or $default if url is invalid or doesn't have a path
	 */
	public static function path($url, $default = false) {
		$result = self::parse($url, 'path');
		return ($result === false) ? $default : $result;
	}

	/**
	 * Convert the current request URL and make it secure
	 *
	 * @return string
	 */
	public static function to_https($u = null) {
		if ($u === null) {
			$u = self::current();
		}
		if (substr($u, 0, 7) === "http://") {
			return "https://" . substr($u, 7);
		}
		return $u;
	}

	/**
	 * Change http://server1/whatever?qs=1#thinig to http://server2/whatever?qs=1#thinig
	 *
	 * Doesn't use URL::parse because it is intended to work on mysql: urls
	 *
	 * @param string $url
	 *        	URL to modify
	 * @param string $host
	 *        	New host to
	 * @return string Modified URL
	 */
	public static function change_host($url, $host) {
		$parts = parse_url($url);
		$parts['host'] = $host;
		return self::unparse($parts);
	}
	protected static $secure_protocols = array(
		'http' => 'https',
		'ftp' => 'sftp',
		'telnet' => 'ssh'
	);

	/**
	 * Returns true if the URL is secure (https)
	 *
	 * @param string $url
	 *        	A URL to test
	 * @return boolean true if the URL is a https URL
	 */
	public static function is_secure($url) {
		return in_array(self::scheme($url, ""), array_values(self::$secure_protocols));
	}

	/**
	 * Returns true if the URL is secure (https)
	 *
	 * @param string $url
	 *        	A URL to test
	 * @return boolean true if the URL is a https URL
	 */
	public static function make_secure($url) {
		if (self::is_secure($url)) {
			return $url;
		}
		$parts = self::parse($url);
		$parts['scheme'] = avalue(self::$secure_protocols, $parts['scheme'], $parts['scheme']);
		return self::unparse($parts);
	}

	/**
	 * Returns true if a url is an absolute URL (for testing href links)
	 *
	 * @param string $url
	 *        	A string to test
	 * @return boolean true if the URL begins with http:// or https://
	 */
	public static function is_absolute($url) {
		$url = trim($url);
		return begins($url, "https://", true) || begins($url, "http://", true);
	}

	/**
	 * Determines if TWO urls reside on the same "server"
	 *
	 * @param string $url1
	 *        	A url to test
	 * @param string $url2
	 *        	Another URL to test
	 * @return boolean true if the schemes, address, port, and host are identical
	 */
	public static function is_same_server($url1, $url2) {
		$p1 = self::parse(strtolower($url1));
		$p2 = self::parse(strtolower($url2));

		if (!is_array($p1) || !is_array($p2)) {
			return false;
		}

		$p1proto = avalue($p1, "scheme", "http");
		$p2proto = avalue($p2, "scheme", "http");

		$p1port = avalue($p1, "port", self::protocol_default_port($p1proto));
		$p2port = avalue($p2, "port", self::protocol_default_port($p2proto));
		if (($p1proto == $p2proto) && (trim(avalue($p1, "host", "")) === trim(avalue($p2, "host", ""))) && ($p1port == $p2port)) {
			return true;
		}
		return false;
	}

	/**
	 * Given a page URL and a href that exists on the page, return the full URL of the href.
	 * Handles relative, absolute, and full URL hrefs
	 *
	 * @param string $url
	 *        	Page where href was found
	 * @param string $href
	 *        	Href on the page
	 * @return string Reconciled href, or false if can not be computed
	 */
	public static function compute_href($url, $href) {
		if (empty($href)) {
			return false;
		}
		if (begins($href, "javascript:")) {
			return false;
		}
		if (self::is($href)) {
			return self::normalize($href);
		}
		$parts = self::parse($url);
		if (begins($href, "//")) {
			$href = $parts['scheme'] . ":" . $href;
			return self::normalize($href);
		}
		if (begins($href, "#")) {
			$parts['fragment'] = substr($href, 1);
			return self::unparse($parts);
		}
		unset($parts['fragment']);
		if (begins($href, "?")) {
			$parts['query'] = substr($href, 1);
			return self::unparse($parts);
		}
		unset($parts['query']);
		if (begins($href, "/")) {
			$parts['path'] = $href;
			return self::unparse($parts);
		}
		$path = $parts['path'];
		$path = dirname($path);
		$path = path($path, $href);
		$path = Directory::undot($path);
		$parts['path'] = $path;
		return self::unparse($parts);
	}
}
