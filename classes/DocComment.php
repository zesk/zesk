<?php
/**
 * DocComment parsing tools
 * 
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class DocComment {
	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string $string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
	static function clean($string) {
		$string = trim($string);
		$string = str::unprefix($string, "/*");
		$string = str::unsuffix($string, "*/");
		$string = explode("\n", $string);
		$string = ArrayTools::trim($string);
		$string = ArrayTools::unprefix($string, "*");
		$string = ArrayTools::trim($string);
		$string = implode("\n", $string);
		
		return $string;
	}
	
	/**
	 * 
	 * @param string $string
	 * @param array $options
	 */
	static function parse($string, $options = array()) {
		$string = self::clean($string);
		$lines = explode("\n", $string);
		$result = array();
		$current_tag = "desc";
		foreach ($lines as $line) {
			$matches = null;
			if (preg_match('/^\@([-A-Za-z_]+)/', $line, $matches)) {
				$current_tag = $matches[1];
				$line = substr($line, strlen($matches[0]));
			}
			$result[$current_tag] = avalue($result, $current_tag, "") . ltrim($line) . "\n";
		}
		// Convert values to a keyed array based on first token in the string
		$multi_keys = to_list(avalue($options, 'multi_keys', array()));
		$multi_keys = array_unique($multi_keys);
		foreach ($multi_keys as $key) {
			if (array_key_exists($key, $result)) {
				$result[$key] = ArrayTools::kpair(ArrayTools::clean(explode("\n", $result[$key])), " ");
			}
		}
		$param_keys = to_list(avalue($options, 'param_keys'));
		$param_keys[] = "property";
		$param_keys[] = "param";
		$param_keys[] = "global"; // Dunno. Are there any other doccomments like this?
		$param_keys = array_unique($param_keys);
		foreach ($param_keys as $key) {
			if (!array_key_exists($key, $result)) {
				continue;
			}
			$lines = ArrayTools::clean(explode("\n", $result[$key]));
			$keys = ArrayTools::field($lines, 1, " \t");
			$values = ArrayTools::field($lines, null, " \t", 3);
			$result[$key] = ArrayTools::rekey($keys, $values);
		}
		// Convert values to arrays
		$list_keys = to_list(avalue($options, 'list_keys'));
		$list_keys[] = "see";
		$list_keys = array_unique($list_keys);
		foreach ($list_keys as $key) {
			if (array_key_exists($key, $result)) {
				$result[$key] = ArrayTools::clean(explode("\n", $result[$key]));
			}
		}
		return ArrayTools::trim($result);
	}
	
	/**
	 * 
	 * @param array $items
	 * @return string
	 */
	static function unparse(array $items) {
		$max_length = 0;
		foreach (array_keys($items) as $name) {
			$max_length = max($max_length, strlen($name));
		}
		$max_length = $max_length + 1;
		foreach ($items as $name => $value) {
			if (!is_array($value)) {
				$value = explode("\n", $value);
			}
			$value = implode("\n *  " . str_repeat(" ", $max_length), $value);
			$spaces = str_repeat(" ", $max_length - strlen($name));
			$result[] = " * @$name$spaces$value";
		}
		return "/**\n" . implode("\n", $result) . "\n */";
	}
	
	/**
	 * Retrieve all DocComment blocks in content as strings. Resulting strings should then be `self::parse`d to determine pairs.
	 * 
	 * @param string $content
	 * @return string[]
	 */
	static function extract($content) {
		$matches = null;
		if (!preg_match_all('#[\t ]*/\*\*[^*]*\*+([^/*][^*]*\*+)*/#se', $content, $matches, PREG_PATTERN_ORDER)) {
			return array();
		}
		return $matches[0];
	}
}
