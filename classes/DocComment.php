<?php
declare(strict_types=1);
/**
 * DocComment parsing tools
 *
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	 * @name key0 value0 and things
	 * @name key1 value1 and others
	 *
	 * Converted to:
	 *
	 *     [ "key0" => "value0 and things", "key1" => "value1 and others" ]
	 *
	 * @var string
	 */
	public const OPTION_MULTI_KEYS = 'multi_keys'; // List of keys which

	/**
	 * For patterns which are keyed by the second token in the DocComment
	 *
	 * @property type0 $var0 Comments about var0
	 * @property type1 $var1 Comments about var1
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
	public const OPTION_PARAM_KEYS = 'param_keys';

	/**
	 * For patterns which are lists of items and
	 *
	 * @see ClassName
	 * @see OtherClassName, DocComment
	 *
	 * Converted to:
	 *
	 *     [
	 *         'see' => [ 'ClassName", 'OtherClassName, DocComment' ],
	 *     ]
	 *
	 * @var string
	 */
	public const OPTION_LIST_KEYS = 'list_keys';

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
	public const OPTION_DESC_NO_TAG = 'desc_no_tag';

	/**
	 *
	 * @var string
	 */
	private string $content = '';

	/**
	 * We are formatting
	 *
	 * @var bool
	 */
	private bool $format_mode;

	private bool $_parsed = false;

	/**
	 *
	 * @var array
	 */
	private array $variables = [];

	/**
	 * String is parsed, array is unparsed
	 *
	 * @param array|string $content
	 * @param array $options
	 */
	public function __construct(array|string $content, array $options = []) {
		parent::__construct($options);
		if (is_array($content)) {
			$this->setVariables($content);
			$this->format_mode = true;
		} else {
			$this->setContent($content);
			$this->format_mode = false;
		}
	}

	/**
	 * Retrieve all DocComment blocks in content as strings. Resulting strings are ready to be consumed.
	 *
	 * @param string $content
	 * @param array $options
	 * @return DocComment[]
	 */
	public static function extract(string $content, array $options = []): array {
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
	 * @param array|string $content
	 * @param array $options
	 * @return self
	 */
	public static function instance(array|string $content, array $options = []): self {
		return new self($content, $options);
	}

	/**
	 * Removes stars from beginning and end of DocComments
	 *
	 * @param string $string A DocComment string to clean
	 * @return string the cleaned DocComment
	 */
	public static function clean(string $string): string {
		// Clean ends of our strings
		$string = trim($string);
		// Remove /* and */ from string ends
		$string = StringTools::removePrefix($string, '/*');
		$string = StringTools::removeSuffix($string, '*/');
		// Break into lines
		$string = explode("\n", $string);
		// Trim all lines for whitespace
		$string = ArrayTools::trim($string);
		// Remove prefix "*" from any line
		$string = ArrayTools::valuesRemovePrefix($string, '*');
		// And trim whitespace one final time
		$string = ArrayTools::trim($string);
		// What we have left is a clean string with the DocComment annotation removed and all whitespace removed
		// at the start and end of each line
		// blank lines are kept intact
		return implode("\n", $string);
	}

	/**
	 * Set the variables for this DocComment
	 *
	 * @param array $set
	 * @return $this
	 */
	public function setVariables(array $set): self {
		$this->variables = $set;
		$this->format_mode = true;
		return $this;
	}

	/**
	 * Retrieve the variables for the DocComment
	 *
	 * @return array
	 */
	public function variables(): array {
		if ($this->format_mode) {
			return $this->variables;
		}
		return $this->parse($this->content);
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setContent(string $set): self {
		$this->content = $set;
		return $this;
	}

	/**
	 * @return string
	 */
	public function content(): string {
		return $this->format_mode ? $this->format($this->variables) : $this->content;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->content();
	}

	/**
	 *
	 * @return array
	 */
	private function multiKeys(): array {
		$keys = $this->optionIterable(self::OPTION_MULTI_KEYS);
		return array_unique($keys);
	}

	/**
	 *
	 *
	 * @param string $lines
	 * @return array
	 */
	private function parseMultiKey(string $lines): array {
		return ArrayTools::pairValues(ArrayTools::clean(explode("\n", $lines)), ' ');
	}

	private function formatMultiKey($value, $key): array {
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
	private function parameterKeys(): array {
		$keys = $this->optionIterable(self::OPTION_PARAM_KEYS);
		$keys[] = 'property';
		$keys[] = 'param';
		$keys[] = 'global'; // Dunno. Are there any other doccomments like this?
		return array_unique($keys);
	}

	private function listKeys(): array {
		$keys = $this->optionIterable(self::OPTION_LIST_KEYS);
		$keys[] = 'see';
		return array_unique($keys);
	}

	/**
	 * @param string $value
	 * @return array
	 */
	private function parseParameterKey(string $value): array {
		$lines = ArrayTools::clean(toList($value, [], "\n"), ['', null]);
		$keys = StringTools::column($lines, 1, " \t");
		$values = [];
		foreach ($lines as $line) {
			$values[] = StringTools::field($line, null, " \t", 3);
		}
		return ArrayTools::rekey($keys, $values);
	}

	/**
	 *
	 * @param array $value
	 * @param string $key
	 * @return array
	 */
	private function formatParameterKey(array $value, string $key): array {
		$result = [];
		foreach ($value as $variable_name => $type_name_etc) {
			$result[] = "@$key " . implode(' ', $type_name_etc);
		}
		return $result;
	}

	/**
	 * Handle items which may have more than one entry and is a simple list
	 *
	 * @param string $value
	 * @return array
	 */
	private function parse_list_key(string $value): array {
		return ArrayTools::listTrimClean(explode("\n", $value));
	}

	private function formatListKey($value, $key): string {
		if (!is_array($value)) {
			$value = ArrayTools::listTrimClean(explode("\n", $value));
		}
		$prefix = "@$key ";
		return $prefix . implode("\n$prefix", $value);
	}

	/**
	 *
	 * @param string $string
	 */
	private function parse(string $string): array {
		$string = self::clean($string);
		$lines = explode("\n", $string);
		$result = [];
		$current_tag = 'desc';
		foreach ($lines as $line) {
			$matches = null;
			if (preg_match('/^\@([-A-Za-z_]+)/', $line, $matches)) {
				$current_tag = $matches[1];
				$line = substr($line, strlen($matches[0]));
			}
			$old_value = $result[$current_tag] ?? '';
			if ($old_value) {
				$old_value .= "\n ";
			}
			$result[$current_tag] = rtrim($old_value, ' ') . trim($line);
		}
		// Convert values to a keyed array based on first token in the string
		$handled = [];
		foreach ($this->multiKeys() as $key) {
			if (isset($result[$key]) && !isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parseMultiKey($result[$key]);
			}
		}
		foreach ($this->parameterKeys() as $key) {
			if (isset($result[$key]) && !isset($handled[$key])) {
				$handled[$key] = true;
				$result[$key] = $this->parseParameterKey($result[$key], $key);
			}
		}
		// Remaining keys turn into arrays or strings depending
		foreach ($this->listKeys() as $key) {
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
	private function formatDefault(string|array $value, string $key): string {
		$spaces = str_repeat(' ', strlen($key) + 2);
		$join = "\n$spaces";
		return "@$key " . (is_array($value) ? implode($join, $value) : implode($join, explode("\n", $value)));
	}

	/**
	 *
	 * @param array $items
	 * @return string
	 */
	private function format(array $items): string {
		$multi_keys = ArrayTools::keysFromValues($this->multiKeys(), true);
		$param_keys = ArrayTools::keysFromValues($this->parameterKeys(), true);
		$list_keys = ArrayTools::keysFromValues($this->listKeys(), true);

		$result = [];
		if ($this->optionBool(self::OPTION_DESC_NO_TAG) && isset($items['desc'])) {
			$value = toList(trim($items['desc']), [], "\n");
			$value = trim(implode("\n", ArrayTools::trim($value))) . "\n";
			$result[] = $value;
			unset($items['desc']);
		}
		foreach ($items as $key => $value) {
			if (isset($multi_keys[$key])) {
				$unparsed = $this->formatMultiKey($value, $key);
			} elseif (isset($param_keys[$key])) {
				$unparsed = $this->formatParameterKey($value, $key);
			} elseif (isset($list_keys[$key])) {
				$unparsed = $this->formatListKey($value, $key);
			} else {
				$unparsed = $this->formatDefault($value, $key);
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
