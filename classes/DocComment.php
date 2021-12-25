<?php declare(strict_types=1);
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
	 * For patterns which are keyed by the first token in the DocComment
	 *
	 *     @name key0 value0 and things
	 *     @name key1 value1 and others
	 *
	 * Converted to:
	 *
	 *     [ "key0" => "value0 and things", "key1" => "value1 and others" ]
	 *
	 * @var string
	 */
	public const OPTION_MULTI_KEYS = "multi_keys"; // List of keys which

	/**
	 * For patterns which are keyed by the second token in the DocComment
	 *
	 *     @property type0 $var0 Comments about var0
	 *     @property type1 $var1 Comments about var1
	 *
	 * Converted to:
	 *
	 *     [
	 *         '$var0' => [ 'type0", '$var0', 'Comments about var0' ],
	 *         '$var1' => [ 'type1", '$var1', 'Comments about var1' ],
	 *     ]
	 *
	 * @var string
	 */
	public const OPTION_PARAM_KEYS = "param_keys";

	/**
	 * For patterns which are lists of items and
	 *
	 *     @see ClassName
	 *     @see OtherClassName, DocComment
	 *
	 * Converted to:
	 *
	 *     [
	 *         'see' => [ 'ClassName", 'OtherClassName, DocComment' ],
	 *     ]
	 *
	 * @var string
	 */
	public const OPTION_LIST_KEYS = "list_keys";

	/**
	 * For output, move @desc tags to the top of the DocComment and place a space between it and other content.
	 *
	 * So:
	 *
	 * DocComment::instance(["desc" => "Hello, world", "see" => "\\zesk\\Kernel"], [OPTION_DESC_NO_TAG => true])->content()
	 *
	 * Converts to:
	 *
	 *     /X**
	 *      * Hello, world
	 *      *
	 *      * @see \zesk\Kernel
	 *      *X/
	 *
	 * Without the X.
	 *
	 * @var string
	 */
	public const OPTION_DESC_NO_TAG = "desc_no_tag";

	/**
	 *
	 * @var string
	 */
	private $content = "";

	/**
	 *
	 * @var array
	 */
	private $variables = [];

	/**
	 * Retrieve all DocComment blocks in content as strings. Resulting strings are ready to be consumed.
	 *
	 * @param string $content
	 * @return DocComment[]
	 */
	public static function extract($content, array $options = []) {
		$matches = null;
		if (!preg_match_all('#[\t ]*/\*\*[^*]*\*+([^/*][^*]*\*+)*/#s', $content, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
			return [];
		}
		$result = [];
		foreach ($matches[0] as $index => $match) {
			$options['index'] = $index;
			$options['length'] = strlen($content);
			[$content, $options['offset']] = $match;
			$result[] = DocComment::instance($content, $options);
		}
		return $result;
	}

	/**
	 *
	 * @param unknown $content
	 * @param array $options
	 * @return \zesk\DocComment
	 */
	public static function instance($content, array $options = []) {
		return new self($content, $options);
	}

	/**
	 * String is parsed, array is unparsed
	 *
	 * @param string|array $content
	 */
	public function __construct($content, array $options = []) {
		parent::__construct($options);
		if (is_array($content)) {
			$this->variables($content);
		} else {
			$this->content(strval($content));
		}
	}

	/**
	 * Removes stars from beginning and end of doccomments
	 *
	 * @param string $string A doccomment string to clean
	 * @return string the cleaned doccomment
	 */
	public static function clean($string) {
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
		$keys = $this->option_list(self::OPTION_MULTI_KEYS);
		$keys = array_unique($keys);
		return $keys;
	}

	private function parse_multi_key($value, $key) {
		return ArrayTools::kpair(ArrayTools::clean(explode("\n", $value)), " ");
	}

	private function unparse_multi_key($value, $key) {
		if (!is_array($value)) {
			return [
				"@$key $value",
			];
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
		$keys = $this->option_list(self::OPTION_PARAM_KEYS);
		$keys[] = "property";
		$keys[] = "param";
		$keys[] = "global"; // Dunno. Are there any other doccomments like this?
		$keys = array_unique($keys);
		return $keys;
	}

	private function list_keys() {
		$keys = $this->option_list(self::OPTION_LIST_KEYS);
		$keys[] = "see";
		$keys = array_unique($keys);
		return $keys;
	}

	private function parse_param_key($value, $key) {
		$lines = ArrayTools::clean(to_list($value, [], "\n"));
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
		$result = [];
		foreach ($value as $variable_name => $type_name_etc) {
			$result[] = "@$key " . implode(" ", $type_name_etc);
		}
		return $result;
	}

	/**
	 * Handle items which may have more than one entry and is a simple list
	 *
	 * @param mixed $value
	 * @param string $key
	 * @return string|array
	 */
	private function parse_list_key($value, $key) {
		$value = ArrayTools::trim_clean(explode("\n", $value));
		return $value;
	}

	private function unparse_list_key($value, $key) {
		if (!is_array($value)) {
			$value = ArrayTools::trim_clean(explode("\n", $value));
		}
		$prefix = "@$key ";
		return $prefix . implode("\n$prefix", $value);
	}

	public function variables(array $set = null) {
		if ($set === null) {
			if ($this->variables === null) {
				return $this->parse($this->content);
			}
			return $this->variables;
		}
		$this->variables = $set;
		$this->content = null;
		return $this;
	}

	public function content($set = null) {
		if ($set === null) {
			if ($this->content === null) {
				return $this->unparse($this->variables);
			}
			return $this->content;
		}
		$this->content = $set;
		$this->variables = null;
		return $this;
	}

	public function __toString() {
		return $this->content();
	}

	/**
	 *
	 * @param string $string
	 * @param array $options
	 */
	private function parse($string) {
		$string = self::clean($string);
		$lines = explode("\n", $string);
		$result = [];
		$current_tag = "desc";
		foreach ($lines as $line) {
			$matches = null;
			if (preg_match('/^\@([-A-Za-z_]+)/', $line, $matches)) {
				$current_tag = $matches[1];
				$line = substr($line, strlen($matches[0]));
			}
			$old_value = $result[$current_tag] ?? "";
			if ($old_value) {
				$old_value .= "\n ";
			}
			$result[$current_tag] = rtrim($old_value, " ") . trim($line);
		}
		// Convert values to a keyed array based on first token in the string
		$handled = [];
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
		foreach ($this->list_keys() as $key) {
			if (isset($result[$key]) && !isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parse_list_key($result[$key], $key);
			}
		}
		if (empty($result['desc'])) {
			unset($result['desc']);
		}
		return ArrayTools::trim($result);
	}

	/**
	 * Add spaces after newlines to maintain indentation for multi-line
	 *
	 * @param string|array $value
	 * @param string $key
	 * @return string
	 */
	private function unparse_default($value, $key) {
		$spaces = str_repeat(" ", strlen($key) + 2);
		$join = "\n$spaces";
		return "@$key " . (is_array($value) ? implode($join, $value) : implode($join, explode("\n", $value)));
	}

	/**
	 *
	 * @param array $items
	 * @return string
	 */
	private function unparse(array $items) {
		$multi_keys = ArrayTools::flip_assign($this->multi_keys(), true);
		$param_keys = ArrayTools::flip_assign($this->param_keys(), true);
		$list_keys = ArrayTools::flip_assign($this->list_keys(), true);

		$result = [];
		if ($this->option_bool(self::OPTION_DESC_NO_TAG) && isset($items['desc'])) {
			$value = to_list(trim($items['desc']), [], "\n");
			$value = trim(implode("\n", ArrayTools::trim($value))) . "\n";
			$result[] = $value;
			unset($items['desc']);
		}
		foreach ($items as $key => $value) {
			if (isset($multi_keys[$key])) {
				$unparsed = $this->unparse_multi_key($value, $key);
			} elseif (isset($param_keys[$key])) {
				$unparsed = $this->unparse_param_key($value, $key);
			} elseif (isset($list_keys[$key])) {
				$unparsed = $this->unparse_list_key($value, $key);
			} else {
				$unparsed = $this->unparse_default($value, $key);
			}
			if (is_string($unparsed)) {
				$result[] = $unparsed;
			} elseif (is_array($unparsed)) {
				$result[] = implode("\n", $unparsed);
			}
		}
		$uncommented = explode("\n", implode("\n", $result));
		foreach ($uncommented as $index => $line) {
			$uncommented[$index] = rtrim($line);
		}
		// Add comments
		return "/**\n * " . implode("\n * ", $uncommented) . "\n */";
	}
}
