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
class DocComment extends Options {

	/**
	 *
	 * @var string
	 */
	private $content = "";

	/**
	 *
	 * @var array
	 */
	private $variables = array();

	/**
	 * String is parsed, array is unparsed
	 *
	 * @param string|array $content
	 */
	function __construct($content, array $options = array()) {
		parent::__construct($options);
		if (is_array($content)) {
			$this->variables($content);
		} else {
			$this->content(strval($content));
		}
	}

	/**
	 *
	 * @param unknown $content
	 * @param array $options
	 * @return \zesk\DocComment
	 */
	static function instance($content, array $options = array()) {
		return new self($content, $options);
	}

	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string $string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
	static function clean($string) {
		$string = trim($string);
		$string = StringTools::unprefix($string, "/*");
		$string = StringTools::unsuffix($string, "*/");
		$string = explode("\n", $string);
		$string = ArrayTools::trim($string);
		$string = ArrayTools::unprefix($string, "*");
		$string = ArrayTools::trim($string);
		$string = implode("\n", $string);

		return $string;
	}

	/**
	 *
	 * @return array
	 */
	private function multi_keys() {
		$keys = $this->option_list("multi_keys");
		$keys = array_unique($keys);
		return $keys;
	}
	private function parse_multi_key($value, $key) {
		return ArrayTools::kpair(ArrayTools::clean(explode("\n", $value)), " ");
	}
	private function unparse_multi_key($value, $key) {
		if (!is_array($value)) {
			return array(
				"@$key $value"
			);
		}
		foreach ($value as $name => $value) {
			$result[] = "@$key $name $value";
		}
		return $result;
	}

	/**
	 * Format is @foo
	 * @return array
	 */
	private function param_keys() {
		$keys = $this->option_list("param_keys");
		$keys[] = "property";
		$keys[] = "param";
		$keys[] = "global"; // Dunno. Are there any other doccomments like this?
		$keys = array_unique($keys);
		return $keys;
	}
	private function parse_param_key($value, $key) {
		$lines = ArrayTools::clean(explode("\n", $value));
		$keys = ArrayTools::field($lines, 1, " \t");
		$values = ArrayTools::field($lines, null, " \t", 3);
		return ArrayTools::rekey($keys, $values);
	}

	/**
	 *
	 * @param array $value
	 * @param string $key
	 * @return string
	 */
	private function unparse_param_key(array $value, $key) {
		foreach ($value as $variable_name => $type_name_etc) {
			$result[] = "@$key " . implode(" ", $type_name_etc);
		}
		return implode("\n", $result);
	}

	/**
	 * Handle items which may have more than one entry
	 *
	 * @param mixed $value
	 * @param string $key
	 * @return string|array
	 */
	private function parse_list_key($value, $key) {
		$value = ArrayTools::clean(explode("\n", $value));
		if (count($value) === 1) {
			return first($value);
		}
		return $value;
	}
	private function unparse_list_key($value, $key) {
		if (is_array($value)) {
			return "@$key " . implode("\n\t", $value);
		}
		return "@$key $value";
	}
	/**
	 *
	 * @param string $string
	 * @param array $options
	 */
	private function parse($string) {
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
		$handled = array();
		foreach ($this->multi_keys() as $key) {
			if (isset($result[$key]) && !isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parse_multi_key($result[$key], $key);
			}
		}
		foreach ($this->param_keys() as $key) {
			if (isset($result[$key]) && !isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parse_param_key($result[$key], $key);
			}
		}
		// Remaining keys turn into arrays or strings depending
		foreach (array_keys($result) as $key) {
			if (!isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parse_list_key($result[$key], $key);
			}
		}
		if (empty($result['desc'])) {
			unset($result['desc']);
		}
		return ArrayTools::trim($result);
	}
	public function variables(array $set = null) {
		if ($set === null) {
			return $this->variables;
		}
		$this->variables = $set;
		$this->content = $this->unparse($set);
		return $this;
	}
	public function content($set = null) {
		if ($set === null) {
			return $this->content;
		}
		$this->content = $set;
		$this->variables = $this->parse($set);
		return $this;
	}
	public function __toString() {
		return $this->content;
	}
	/**
	 *
	 * @param array $items
	 * @return string
	 */
	private function unparse(array $items) {
		$result = array();
		foreach ($this->multi_keys() as $key) {
			if (isset($items[$key])) {
				$result[$key] = $this->unparse_multi_key($items[$key], $key);
				unset($items[$key]);
			}
		}
		foreach ($this->param_keys() as $key) {
			if (isset($items[$key]) && is_array($items[$key])) {
				$result[$key] = $this->unparse_param_key($items[$key], $key);
				unset($items[$key]);
			}
		}
		foreach ($items as $key => $value) {
			$result[$key] = $this->unparse_list_key($items, $key);
		}
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
	 * @return DocComment[]
	 */
	static function extract($content, array $options = array()) {
		$matches = null;
		if (!preg_match_all('#[\t ]*/\*\*[^*]*\*+([^/*][^*]*\*+)*/#se', $content, $matches, PREG_PATTERN_ORDER)) {
			return array();
		}
		$result = array();
		foreach ($matches[0] as $content) {
			$result[] = DocComment::instance($content, $options);
		}
		return $result;
	}
}
