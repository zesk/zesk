<?php

/**
 * Handles translation from a local file system path to a content-delivery network path
 *
 * TODO Move this into a url filter and remove CDN from zesk API
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/CDN.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

zesk()->deprecated();

/**
 *
 * @author kent
 *
 */
class CDN {
	
	/**
	 * Whether the CDN list is sorted
	 *
	 * @var boolean
	 */
	static $sorted = false;
	static $paths = array();
	static $defaults = array();
	public static function url($uri = "") {
		$prefix = avalue(self::$defaults, 0, '');
		if ($uri !== "") {
			if (!self::$sorted) {
				self::sort();
			}
			foreach (self::$paths as $pattern => $items) {
				if (!preg_match($pattern, $uri, $matches)) {
					continue;
				}
				$prefix = map($items[0], $matches);
				$uri = substr($uri, strlen($matches[0]));
				break;
			}
		}
		return Directory::strip_slash($prefix) . '/' . ltrim($uri, '/');
	}
	public static function path($uri = "") {
		$prefix = avalue(self::$defaults, 1, '');
		if ($uri !== "") {
			self::sort();
			foreach (self::$paths as $pattern => $items) {
				$matches = null;
				if (!preg_match($pattern, $uri, $matches)) {
					continue;
				}
				$prefix = map($items[1], $matches);
				$uri = substr($uri, strlen($matches[0]));
				break;
			}
		}
		return path($prefix, $uri);
	}
	private static function sort() {
		if (!self::$sorted) {
			self::$sorted = true;
			uksort(self::$paths, __CLASS__ . '::compare');
		}
	}
	public static function compare($a, $b) {
		return strlen($b) - strlen($a);
	}
	
	/**
	 * Add a mapping from a path to a CDN path.
	 * Optionally do variable substitution if needed.
	 *
	 * Variables are any sequance of non-slash characters, to allow paths to be mapped elsewhere.
	 *
	 * @param string $pattern
	 *        	Usually a prefix for content, such as "/share/", or /application/{app}
	 * @param string $url_prefix
	 *        	When the above pattern is found, prefix the entire URL with this prefix
	 *        	(substitution occurs)
	 * @param string $root_dir
	 *        	The actual location in the file system of the resource
	 * @param boolean $create
	 *        	If direction not found, create it
	 * @throws Exception_Directory_NotFound
	 */
	public static function add($pattern, $url_prefix, $root_dir, $create = false) {
		$variables = array();
		if (preg_match_all('/{([^}]+)}/', $pattern, $matches, PREG_SET_ORDER)) {
			if ($create) {
				throw new Exception_Semantics("Can not create directory when pattern contains variables: $pattern");
			}
			foreach ($matches as $match) {
				list($full_match, $variable) = $match;
				if (in_array($variable, $variables)) {
					throw new Exception_Semantics("Variable $variable appears twice in input pattern: $pattern");
				}
				$variables[] = $variable;
				$index = count($variables);
				$pattern = str_replace($full_match, '([^/]+)', $pattern);
				$root_dir = str_replace($full_match, '{' . $index . '}', $root_dir);
				$url_prefix = str_replace($full_match, '{' . $index . '}', $url_prefix);
			}
		} else {
			if (!is_dir($root_dir)) {
				if ($create) {
					global $zesk;
					Directory::create($root_dir, 0775);
					$zesk->hooks->call("directory_created", $root_dir);
				}
				throw new Exception_Directory_NotFound($root_dir);
			}
		}
		if (empty($pattern)) {
			self::$defaults = array(
				$url_prefix,
				$root_dir
			);
		} else {
			$pattern = "|^$pattern|";
			self::$paths[$pattern] = array(
				$url_prefix,
				$root_dir,
				$variables
			);
			self::$sorted = false;
		}
	}
}
