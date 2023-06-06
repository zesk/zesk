<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use function trim;

/**
 * Difference between zesk\URL and \URL:: is that we can do new zesk\URL($url) (TODO)
 */
class URL {
	/**
	 * Convert a non-secure protocol into a more secure one.
	 *
	 * @var array
	 */
	protected static array $secureProtocols = ['http' => 'https', 'ftp' => 'sftp', 'telnet' => 'ssh', ];

	/**
	 * What's the order for items in a URL (typically http URLs)?
	 *
	 * @var array
	 */
	public static array $urlPartOrdering = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment', ];

	/**
	 * Query string parsing (case-sensitive)
	 *
	 * @param string $qs
	 *            Query string to parse (Does NOT parse URLs)
	 * @param string|null $name
	 *            Name of field to return, or null to return an array
	 * @param string $default
	 *            Value to return if name not found in query string
	 * @return mixed Parsed query string
	 */
	public static function queryParse(string $qs, string $name = null, mixed $default = null): array {
		if (empty($qs)) {
			return [];
		}
		$res = [];
		parse_str($qs, $res);
		if (!is_array($res)) {
			return [];
		}
		if (is_string($name)) {
			return $res[$name] ?? $default;
		}
		return $res;
	}

	/**
	 * Case-insensitive query string parsing
	 *
	 * @param string $qs
	 *            Query string to parse (Does NOT parse URLs)
	 * @param string $name
	 *            Name of field to return, or null to return an array
	 * @param ?string $default
	 *            Value to return if name not found in query string
	 * @return ?string Parsed query string
	 */
	public static function queryParseInsensitive(string $qs, string $name, string $default = null): ?string {
		$res = [];
		parse_str($qs, $res);
		if (!is_array($res)) {
			return $default;
		}
		$res = array_change_key_case($res);
		return $res[strtolower($name)] ?? $default;
	}

	/**
	 * Similar to parse_str.
	 * Returns false if the query string or URL is empty. Because we're not parsing to
	 * variables but to array key entries, this function will handle ?[]=1&[]=2 "correctly."
	 *
	 * Use this instead? Issues with . in variables names for parse_str may mean: use this instead
	 *
	 * @param string $url
	 *            A query string or URL
	 * @param boolean $qmark
	 *            Find and strip out everything before the question mark in the string
	 * @param boolean $simple
	 *            Do not parse PHP arrays, just create values for them
	 * @return array Similar to the $_GET formatting that PHP does automagically.
	 */
	public static function queryParseURL(string $url, bool $qmark = true, bool $simple = false): array {
		if ($qmark) {
			$url = StringTools::right($url, '?');
			$url = StringTools::left($url, '#');
		}
		if (empty($url)) {
			return [];
		}
		$tokens = explode('&', $url);
		$urlVars = [];
		foreach ($tokens as $token) {
			[$token, $value] = StringTools::pair($token, '=', $token);
			$matches = false;
			if (!$simple && preg_match('/^([^\[]*)(\[.*])$/', $token, $matches)) {
				self::_queryParseArray($urlVars, $matches[1], $matches[2], $value);
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
	 * @param array $result
	 *            A result array to populate from the query string
	 * @param string $k
	 *            The starting key to populate in $result
	 * @param string $arrayKeys
	 *            The key list to parse in the form "[][a][what%20ever]"
	 * @param string $value
	 *            The value to place at the destination array key
	 * @return void
	 */
	private static function _queryParseArray(array &$result, string $k, string $arrayKeys, string $value): void {
		$matches = [];
		if (!preg_match_all('/\[([^]]*)]/', $arrayKeys, $matches)) {
			return;
		}
		if (!isset($result[$k])) {
			$result[urldecode($k)] = [];
		}
		$temp = &$result[$k];
		$last = urldecode(array_pop($matches[1]));
		foreach ($matches[1] as $k) {
			$k = urldecode($k);
			if ($k === '') {
				$temp[] = [];
				$temp = &$temp[count($temp) - 1];
			} elseif (!isset($temp[$k])) {
				$temp[$k] = [];
				$temp = &$temp[$k];
			}
		}
		if ($last === '') {
			$temp[] = $value;
		} else {
			$temp[urldecode($last)] = $value;
		}
	}

	/**
	 *
	 * @param string $path
	 * @param string|array $add Name/value pairs to replace
	 * @param string[] $remove List of items to remove
	 * @return string
	 */
	public static function queryFormat(string $path, string|array $add = [], array|string $remove = []): string {
		[$uri, $qs] = StringTools::pair($path, '?', $path);
		if ($qs === '') {
			$qs = [];
		} else {
			$qs = self::queryParse($qs);
		}
		$remove = Types::toList($remove);
		foreach ($remove as $k) {
			unset($qs[$k]);
		}
		if (is_string($add)) {
			$add = self::queryParse($add);
		}
		if (is_array($add)) {
			$qs = ArrayTools::merge($qs, $add);
		}
		return $uri . self::queryToString($qs);
	}

	/**
	 * Convert multidimensional arrays into a query string which PHP supports for reconstruction.
	 *
	 * @param string $key_name
	 * @param array $qs
	 * @return string
	 */
	private static function queryArrayToString(string $key_name, array $qs): string {
		$item = [];
		foreach ($qs as $k => $v) {
			$name = urldecode($key_name) . '[' . urlencode($k) . ']';
			if (is_array($v)) {
				$item[] = self::queryArrayToString($name, $v);
			} else {
				$item[] = $name . '=' . urlencode($v);
			}
		}
		return implode('&', $item);
	}

	/**
	 * Convert an array into a query string
	 *
	 * @param array $qs
	 * @return string
	 */
	public static function queryToString(array $qs): string {
		if (count($qs) === 0) {
			return '';
		}
		$item = [];
		foreach ($qs as $k => $v) {
			if (is_array($v)) {
				$item[] = self::queryArrayToString($k, $v);
			} else {
				$item[] = urlencode($k) . '=' . urlencode($v ?? '');
			}
		}
		return '?' . implode('&', $item);
	}

	/**
	 * Append to the query string of a URL
	 *
	 * @param string $url
	 * @param mixed $values
	 *            Array or string to append
	 * @return string
	 */
	public static function queryAppend(string $url, array|string $values): string {
		$amp = '&';
		if (is_array($values)) {
			$qs_append = [];
			foreach ($values as $k => $v) {
				if (is_array($v)) {
					$qs_append[] = self::queryArrayToString($k, $v);
				} else {
					$qs_append[] = urlencode($k) . '=' . urlencode($v);
				}
			}
			$qs_append = implode($amp, $qs_append);
		} else {
			$qs_append = $values;
		}
		if (strval($qs_append) === '') {
			return $url;
		}
		$sep = (!str_contains($url, '?')) ? '?' : $amp;
		return $url . $sep . $qs_append;
	}

	/**
	 * Remove items from a query string
	 *
	 * @param string $url
	 * @param array|string $names A ;-separated list of query string names to remove, case-sensitive.
	 * @return string
	 */
	public static function queryKeysRemove(string $url, array|string $names): string {
		[$url, $m] = StringTools::pair($url, '#', $url);
		$x = strpos($url, '?');
		if ($x === false) {
			return $url;
		}
		$q = substr($url, $x + 1);
		$newUrl = substr($url, 0, $x);
		$q = explode('&', $q);
		$nq = [];
		foreach ($q as $i) {
			$kv = explode('=', $i, 2);
			if (!Lists::contains($names, $kv[0])) {
				$nq[] = $i;
			}
		}
		$m = ($m ? "#$m" : '');
		if (count($nq) == 0) {
			return $newUrl . $m;
		}
		return $newUrl . '?' . implode('&', $nq) . $m;
	}

	/**
	 * Remove items from a query string using case-insensitive string matching
	 *
	 * @param string $url URL to remove query string variables from
	 * @param array|string $names `;` separated list or array of strings of query string variables to remove
	 * @return string The URL with the query string variables removed
	 */
	public static function queryKeysRemoveInsensitive(string $url, array|string $names): string {
		$m = StringTools::right($url, '#', '');
		$m = ($m ? "#$m" : '');
		$x = strpos($url, '?');
		if ($x === false) {
			return $url;
		}
		$q = substr($url, $x + 1);
		$newUrl = substr($url, 0, $x);
		$qs = [];
		parse_str($q, $qs);
		$names = ArrayTools::changeValueCase($names);
		foreach ($qs as $k => $v) {
			if (in_array(strtolower($k), $names)) {
				unset($qs[$k]);
				if (count($qs) === 0) {
					break;
				}
			}
		}
		if (count($qs) === 0) {
			return $newUrl . $m;
		}
		$nq = [];
		foreach ($qs as $k => $v) {
			$nq[] = "$k=" . urlencode($v);
		}
		return $newUrl . '?' . implode('&', $nq) . $m;
	}

	/**
	 * Parse a URL without choking on non-URLs
	 *
	 * @param mixed $url
	 *            URL to parse
	 * @param string $component
	 *            Optional string of component to retrieve. Different from parse_url as it doesn't
	 *            take the constant, just the string component
	 * @param string $default
	 *            Default value to return if component specified is not found
	 * @return mixed An associative array if whole URL is parsed, a string if component, or false if
	 *         parsing failed
	 */
	/**
	 * @param string $url
	 * @return array
	 * @throws SyntaxException
	 */
	public static function parse(string $url): array {
		$url = trim($url);
		if (preg_match('%^[a-z][a-z0-9]*://.+|^mailto:.+@.+%', strtolower($url)) === 0) {
			throw new SyntaxException('Not a URL');
		}
		if (strtolower(substr($url, 0, 7)) === 'file://') {
			$result = ['scheme' => 'file', 'host' => '', 'path' => substr($url, 7), ];
		} else {
			$result = parse_url($url);
			if (!is_array($result)) {
				throw new SyntaxException('parse_url({url}) failed {type}', [
					'type' => Types::type($result), 'url' => $url,
				]);
			}
		}
		foreach ($result as $k => $v) {
			$result[$k] = urldecode(strval($v));
		}
		$result['scheme'] = strtolower($result['scheme'] ?? '');
		if ($result['scheme'] === 'mailto' && array_key_exists('path', $result)) {
			$path = $result['path'];
			unset($result['path']);
			[$user, $host] = StringTools::reversePair($path, '@', '', $path);
			if ($user) {
				$result['user'] = $user;
			}
			if ($host) {
				$result['host'] = $host;
			}
		}
		$result['url'] = URL::stringify($result);
		return $result;
	}

	/**
	 * Takes a URL or url parts (array) and adds useful variables helpful in generating URLs as
	 * variables
	 *
	 * @param string $url
	 * @return array
	 * @throws SyntaxException
	 */
	public static function variables(string $url): array {
		$parts = self::parse($url);
		$parts['url'] = $url;
		if (array_key_exists('host', $parts)) {
			$parts['host:port'] = $parts['host'] . (array_key_exists('port', $parts) ? ':' . $parts['port'] : '');
		}
		if (array_key_exists('scheme', $parts)) {
			$parts['scheme:host:port'] = self::left($url, 'host');
		}
		return $parts;
	}

	/**
	 * Take a URL parsed into parts and convert it back to a string
	 *
	 * @param array $parts
	 * @return string
	 */
	public static function stringify(array $parts): string {
		$scheme = strtolower($parts['scheme'] ?? '');
		$mailto = ($scheme === 'mailto');
		$url = $scheme . ($mailto ? ':' : '://');

		$user = $parts['user'] ?? '';
		$pass = $parts['pass'] ?? '';
		if ($user || $pass) {
			$url .= urlencode($user);
			if (!empty($pass)) {
				$url .= ':' . urlencode($pass);
			}
			$url .= '@';
		}

		$url .= strtolower($parts['host'] ?? '');
		$temp = intval($parts['port'] ?? -1);
		if ($temp > 0 && ($temp !== self::protocolPort($scheme))) {
			$url .= ':' . $parts['port'];
		}
		if (!$mailto) {
			$path = $parts['path'] ?? '';
			if (!empty($path)) {
				if ($path[0] !== '/') {
					$path = "/$path";
				}
			} else {
				$path = '/';
			}
			$url .= $path;
			$temp = $parts['query'] ?? '';
			if ($temp) {
				$url .= '?' . $temp;
			}

			$temp = $parts['fragment'] ?? '';
			if ($temp) {
				$url .= '#' . urlencode($temp);
			}
		}
		return $url;
	}

	/**
	 * Is this a valid URL? a.k.a. URL::is
	 *
	 * @param string $url
	 * @return boolean
	 * @see URL::is
	 */
	public static function valid(string $url): bool {
		return self::is($url);
	}

	/**
	 * Is this a valid URL?
	 *
	 * @param string $url
	 * @return boolean
	 */
	public static function is(string $url): bool {
		try {
			$p = self::parse($url);
		} catch (SyntaxException) {
			return false;
		}
		$s = $p['scheme'] ?? null;
		if ($s !== 'http' && $s !== 'https') {
			return true;
		}
		$a = strtolower($p['host'] ?? '');
		if (preg_match('/[^-a-z.0-9]/', $a)) {
			return false;
		}
		return true;
	}

	/**
	 * Remove the password from a URL if it exists
	 *
	 * @param string $x A URL with a password in it, or not.
	 * @return string The URL without a password
	 * @throws SyntaxException
	 */
	public static function removePassword(string $x): string {
		$parts = self::parse($x);
		unset($parts['pass']);
		return self::stringify($parts);
	}

	/**
	 * Return URL scheme default port. Just uses the obvious ones. (gopher:// anyone?)
	 *
	 * So you can be a good programmer and avoid using constants.
	 *
	 * @param string $scheme Case-insensitive scheme (e.g. "http", "ftp", etc.)
	 * @return integer returns -1 if not found
	 */
	public static function protocolPort(string $scheme): int {
		static $protocols = [
			'ftp' => 21, 'smtp' => 25, 'mailto' => 25, 'http' => 80, 'pop' => 110, 'https' => 443, 'file' => -1, 'telnet' => 23,
		];
		return $protocols[strtolower($scheme)] ?? -1;
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
	 * @param string $url
	 *            A URL to parse
	 * @return string The normalized URL
	 * @throws SyntaxException
	 */
	public static function normalize(string $url): string {
		$p = self::parse($url);
		$proto = strtolower($p['scheme']);
		$proto = strtolower($proto);
		$host = $p['host'];
		if (!empty($host)) {
			$p['host'] = trim(strtolower(urldecode($host)));
		}
		$p['scheme'] = $proto;
		if ($proto !== 'mailto') {
			$p['path'] ??= '/';
		}
		return self::stringify($p);
	}

	/**
	 * Return the left-hand portion of the URL up (and including) to the specified part
	 *
	 * e.g.
	 * <code>
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'query') ===
	 * 'http://www.example.com:80/path/to?query=1'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'port') ===
	 * 'http://www.example.com:80/'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'host') ===
	 * 'http://www.example.com/'
	 * URL::left('http://www.example.com:80/path/to?query=1#frag', 'scheme') ===
	 * 'http://www.example.com/'
	 *
	 * @param string $url
	 *            URL to trim
	 * @param string $part
	 *            Return the left part of the URL up to and including this portion
	 * @return string
	 * @throws SyntaxException
	 */
	public static function left(string $url, string $part): string {
		$parts = self::parse($url);
		$new_parts = [];
		foreach (self::$urlPartOrdering as $part_item) {
			if (array_key_exists($part_item, $parts)) {
				$new_parts[$part_item] = $parts[$part_item];
			}
			if ($part_item === $part) {
				break;
			}
		}
		return self::stringify($new_parts);
	}

	/**
	 * Returns everything in the URL prior to the path, query string, and fragment
	 * e.g.
	 * <pre>http://www.example.com:98/path/index.php?id=323&o=123#top</pre>
	 * becomes
	 * <pre>http://www.example.com:98/</pre>
	 * This is useful to return an absolute path of a resource at the same address.
	 *
	 * @param string $url
	 *            URL to modify
	 * @return string The URL up to the path
	 * @throws SyntaxException
	 */
	public static function leftHost(string $url): string {
		return self::left($url, 'port');
	}

	/**
	 * Returns everything in the URL prior to the query string, and fragment
	 * e.g.
	 * <pre>http://www.example.com:98/path/index.php?id=323&o=123#top</pre>
	 * becomes
	 * <pre>http://www.example.com:98/path/index.php</pre>
	 * This is useful to return an absolute path of a resource at the same address.
	 *
	 * @param string $url URL to modify
	 * @return string The URL up to the path
	 * @throws SyntaxException
	 */
	public static function left_path(string $url): string {
		return self::left($url, 'path');
	}

	/**
	 * Fix common issues with URL formatting, particularly when passed in via query strings, etc.
	 *
	 * @param string $u
	 *            A potentially malformed URL
	 * @return string Repaired URL, or false if repair failed
	 * @throws SyntaxException
	 */
	public static function repair(string $u): string {
		if (str_starts_with($u, 'https%3A//') || str_starts_with($u, 'http%3A//')) {
			$u = urldecode($u);
		} elseif (preg_match('|^https?:/[^/]|', $u)) {
			$u = preg_replace('|^http(s)?:/|', 'http$1://', $u);
		}
		$parts = self::parse($u);
		if (array_key_exists('host', $parts)) {
			$parts['host'] = strtolower(preg_replace('/[^A-Za-z0-9.-]/', '', $parts['host']));
			$u = self::stringify($parts);
		}
		if (!self::is($u)) {
			throw new SyntaxException('Can not repair');
		}
		return $u;
	}

	/**
	 * Returns the scheme of the url
	 *
	 * @param string $url URL to extract the scheme from
	 * @return string The scheme
	 * @throws SyntaxException
	 */
	public static function scheme(string $url): string {
		$result = self::parse($url);
		return $result['scheme'];
	}

	/**
	 * Returns the host of a URL
	 *
	 * @param string $url
	 *            The url to extract the host information from
	 * @return string The host in the URL
	 * @throws SyntaxException
	 */
	public static function host(string $url): string {
		$result = self::parse($url);
		return $result['host'] ?? '';
	}

	/**
	 * Returns the query string of a URL (a=b&c=d)
	 *
	 * @param string $url
	 *            The url to extract the host information from
	 * @return string The query string in the URL (without the ?)
	 * @throws SyntaxException
	 */
	public static function query(string $url): string {
		$parts = self::parse($url);
		return $parts['query'] ?? '';
	}

	/**
	 * Returns the path of a URL (/path/to/index.php)
	 *
	 * @param string $url
	 *            The url to extract the path information from
	 * @return string The path in the URL
	 * @throws SyntaxException
	 */
	public static function path(string $url): string {
		$result = self::parse($url);
		return $result['path'];
	}

	/**
	 * Change http://server1/whatever?qs=1#thinig to http://server2/whatever?qs=1#thinig
	 *
	 * Doesn't use URL::parse because it is intended to work on mysql: urls
	 *
	 * @param string $url
	 *            URL to modify
	 * @param string $host
	 *            New host to
	 * @return string Modified URL
	 * @throws SyntaxException
	 */
	public static function changeHost(string $url, string $host): string {
		$parts = self::parse($url);
		$parts['host'] = $host;
		return self::stringify($parts);
	}

	/**
	 * Returns true if the URL is secure (https)
	 *
	 * @param string $url
	 *            A URL to test
	 * @return boolean true if the URL is a https URL
	 */
	public static function isSecure(string $url): bool {
		try {
			return in_array(self::scheme($url), array_values(self::$secureProtocols));
		} catch (SyntaxException) {
			return false;
		}
	}

	/**
	 * Returns true if the URL is secure (https)
	 *
	 * @param string $url
	 *            A URL to test
	 * @return string Secured URL
	 * @throws SyntaxException
	 * @throws Semantics
	 * @see URL_Test::test_makeSecure()
	 */
	public static function makeSecure(string $url): string {
		if (self::isSecure($url)) {
			return $url;
		}
		$parts = self::parse($url);
		$scheme = $parts['scheme'] ?? '';
		if (!array_key_exists($scheme, self::$secureProtocols)) {
			throw new Semantics('{url} Not a known insecure protocol: {choices}', [
				'url' => $url,
				'choices' => array_keys(self::$secureProtocols),
			]);
		}
		$defaultPort = self::protocolPort($scheme);
		if (intval($parts['port'] ?? $defaultPort) !== $defaultPort) {
			throw new Semantics('{url} Port must be standard to promote: {port} !== {defaultPort}', [
				'url' => $url,
				'defaultPort' => $defaultPort,
				'port' => $parts['port'],
			]);
		}
		$parts['scheme'] = self::$secureProtocols[$scheme];
		$parts['port'] = self::protocolPort($parts['scheme']);
		return self::stringify($parts);
	}

	/**
	 * Returns true if an url is an absolute URL (for testing href links)
	 *
	 * @param string $url
	 *            A string to test
	 * @return boolean true if the URL begins with http:// or https://
	 */
	public static function isAbsolute(string $url): bool {
		$url = trim($url);
		return str_starts_with($url, 'https://') || str_starts_with($url, 'http://');
	}

	/**
	 * Determines if TWO urls reside on the same "server"
	 *
	 * @param string $url1 URL to compare
	 * @param string $url2 URL to compare
	 * @return boolean true if the schemes, host and port are identical
	 */
	public static function isSameServer(string $url1, string $url2): bool {
		try {
			$p1 = self::parse(strtolower($url1));
			$p2 = self::parse(strtolower($url2));
		} catch (SyntaxException) {
			return false;
		}
		if (($p1proto = $p1['scheme'] ?? '') !== ($p2proto = $p2['scheme'] ?? '')) {
			return false;
		}
		if (intval($p1['port'] ?? self::protocolPort($p1proto)) !== intval($p2['port'] ?? self::protocolPort($p2proto))) {
			return false;
		}
		if (($p1['host'] ?? '') !== ($p2['host'] ?? '')) {
			return false;
		}
		return true;
	}

	/**
	 * Given a page URL and a href that exists on the page, return the full URL of the href.
	 * Handles relative, absolute, and full URL hrefs
	 *
	 * @param string $url
	 *            Page where href was found
	 * @param string $href
	 *            Href on the page
	 * @return string Reconciled href
	 * @throws SyntaxException
	 * @throws Semantics
	 */
	public static function computeHREF(string $url, string $href): string {
		if (empty($href)) {
			throw new Semantics('href is blank');
		}
		if (str_starts_with($href, 'javascript:')) {
			throw new Semantics('javascript: href is invalid');
		}
		if (self::is($href)) {
			return self::normalize($href);
		}
		$parts = self::parse($url);
		if (str_starts_with($href, '//')) {
			$href = $parts['scheme'] . ':' . $href;
			return self::normalize($href);
		}
		if (str_starts_with($href, '#')) {
			$parts['fragment'] = substr($href, 1);
			return self::stringify($parts);
		}
		unset($parts['fragment']);
		if (str_starts_with($href, '?')) {
			$parts['query'] = substr($href, 1);
			return self::stringify($parts);
		}
		unset($parts['query']);
		if (str_starts_with($href, '/')) {
			$parts['path'] = $href;
			return self::stringify($parts);
		}
		$path = $parts['path'];
		$path = dirname($path);
		$path = Directory::path($path, $href);
		$path = Directory::removeDots($path);
		$parts['path'] = $path;
		return self::stringify($parts);
	}
}
