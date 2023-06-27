<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use ReflectionObject;
use Throwable;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParseException;
use zesk\Exception\SyntaxException;
use zesk\Exception\UnimplementedException;
use zesk\Exception\ParameterException;
use zesk\Exception\UnsupportedException;

/**
 *
 * @author kent
 *
 */
class PHP
{
	/**
	 * @see PHP::requires
	 * @var string
	 */
	public const FEATURE_POSIX = 'posix';

	/**
	 * @see PHP::requires
	 * @var string
	 */
	public const FEATURE_PROCESS_CONTROL = 'pcntl';

	/**
	 * Constant to set the current script time limit (applies to web server only)
	 *
	 * @see PHP::requires, PHP::setFeature
	 * @var string
	 */
	public const FEATURE_TIME_LIMIT = 'time_limit';

	/**
	 * Constant to set the current script memory limit. Takes an integer, or a string compatible with to_bytes
	 *
	 * @see to_bytes
	 * @see PHP::setFeature
	 * @var string
	 * @see self::MEMORY_LIMIT_UNLIMITED
	 */
	public const FEATURE_MEMORY_LIMIT = 'memory_limit';

	/**
	 * Used with FEATURE_MEMORY_LIMIT to set limit to unlimited. Use with caution.
	 *
	 * @var integer`
	 */
	public const MEMORY_LIMIT_UNLIMITED = -1;

	/**
	 * Character used to indent code
	 *
	 * @var string
	 */
	public string $indent_char = ' ';

	/**
	 * When indenting, use $indent_multiple times $indent_char for each indent level
	 *
	 * @var integer
	 */
	public int $indent_multiple = 4;

	/**
	 * Value to separate array values
	 *
	 * @var string
	 */
	public string $array_value_separator = "\n";

	/**
	 * Put a trailing comma on array output
	 *
	 * @var boolean
	 */
	public bool $array_trailing_comma = true;

	/**
	 * Characters to place before an array arrow =>
	 *
	 * @var string
	 */
	public string $array_arrow_prefix = ' ';

	/**
	 * Characters to place after an array arrow =>
	 *
	 * @var string
	 */
	public string $array_arrow_suffix = ' ';

	/**
	 * Characters to place before an array open parenthesis
	 *
	 * @var string
	 */
	public string $array_open_parenthesis_prefix = '';

	/**
	 * Characters to place after an array open parenthesis
	 *
	 * @var string
	 */
	public string $array_open_parenthesis_suffix = "\n";

	/**
	 * Characters to place before an array close parenthesis
	 *
	 * @var string
	 */
	public string $array_close_parenthesis_prefix = '';

	/**
	 * Characters to place after an array close parenthesis
	 *
	 * @var string
	 */
	public string $array_close_parenthesis_suffix = '';

	/**
	 * Global dump settings, used when called statically
	 *
	 * @var ?self
	 */
	private static ?self $singleton = null;

	/**
	 * Return global static dump object
	 *
	 * @return self
	 */
	public static function singleton(): self
	{
		if (!self::$singleton instanceof self) {
			self::$singleton = new self();
		}
		return self::$singleton;
	}

	/**
	 * Retrieve the php.ini path
	 *
	 * @return string
	 */
	public static function ini_path(): string
	{
		return get_cfg_var('cfg_file_path');
	}

	/**
	 * Set the settings in this object to support one-line output
	 *
	 * @return php
	 */
	public function settingsOneLine(): self
	{
		$this->indent_char = '';
		$this->array_value_separator = ' ';
		$this->array_open_parenthesis_suffix = '';
		$this->array_close_parenthesis_suffix = '';
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public static function dump_settings_one(): array
	{
		return self::singleton()->settingsOneLine()->settings();
	}

	/**
	 * Dump value as PHP
	 *
	 * @param mixed $x
	 * @return string
	 */
	public static function dump(mixed $x): string
	{
		return self::singleton()->render($x);
	}

	/**
	 * Get the settings for this object
	 *
	 * @return array
	 */
	public function settings(): array
	{
		$x = new ReflectionObject($this);
		$result = [];
		foreach ($x->getProperties() as $prop) {
			if ($prop->isPublic()) {
				$result[$prop->getName()] = $prop->getValue($this);
			}
		}
		return $result;
	}

	/**
	 * Get or set the settings in this object
	 *
	 * @param array $set
	 * @return self
	 * @throws KeyNotFound
	 */
	public function setSettings(array $set): self
	{
		$x = new ReflectionObject($this);
		foreach ($set as $property_name => $value) {
			if ($x->hasProperty($property_name)) {
				$property = $x->getProperty($property_name);
				if ($property->isPublic()) {
					$property->setValue($this, $value);
				} else {
					throw new KeyNotFound($property_name);
				}
			} else {
				throw new KeyNotFound($property_name);
			}
		}
		return $this;
	}

	/**
	 * Output PHP structures formatted in PHP language
	 *
	 * @param mixed $x
	 * @return string
	 */
	public function render(mixed $x): string
	{
		$args = func_get_args();
		$no_first_line_indent = Types::toBool($args[2] ?? false);
		if (is_array($x)) {
			if (count($x) === 0) {
				return '[]';
			}
			$indent_level = $args[1] ?? 0;
			$result = ($no_first_line_indent ? '' : str_repeat($this->indent_char, $indent_level * $this->indent_multiple)) . $this->array_open_parenthesis_prefix . '[' . $this->array_open_parenthesis_suffix;
			$items = [];
			if (ArrayTools::isList($x)) {
				foreach ($x as $v) {
					$items[] = str_repeat($this->indent_char, ($indent_level + 1) * $this->indent_multiple) . $this->render($v, $indent_level + 1, true);
				}
			} else {
				foreach ($x as $k => $v) {
					$items[] = str_repeat($this->indent_char, ($indent_level + 1) * $this->indent_multiple) . $this->render($k) . $this->array_arrow_prefix . '=>' . $this->array_arrow_suffix . $this->render($v, $indent_level + 1, true);
				}
			}
			$comma = ',';
			$sep = $comma . $this->array_value_separator;
			$result .= implode($sep, $items);
			$result .= $this->array_trailing_comma ? $comma : '';
			$result .= $this->array_value_separator;
			$result .= str_repeat($this->indent_char, $indent_level * $this->indent_multiple) . $this->array_close_parenthesis_prefix . ']' . $this->array_close_parenthesis_suffix;
			return $result;
		} elseif (is_string($x)) {
			return '"' . addcslashes($x, "\$\"\\\0..\37") . '"';
		} elseif (is_int($x)) {
			return "$x";
		} elseif ($x === null) {
			return 'null';
		} elseif (is_bool($x)) {
			return $x ? 'true' : 'false';
		} elseif (is_object($x)) {
			if (method_exists($x, '_to_php')) {
				return $x->_to_php();
			}
			if (method_exists($x, '__toString')) {
				return 'new ' . $x::class . '(' . $x . ')';
			}
			return 'new ' . $x::class . '()';
		} else {
			return strval($x);
		}
	}

	/**
	 * Exception logged during unserialize
	 *
	 * @var ?SyntaxException
	 */
	protected static ?SyntaxException $unserialize_exception = null;

	/**
	 * Temporary error handler during unserialize
	 *
	 * @param int $errno
	 * @param string $errorString
	 */
	public static function _unserialize_handler(int $errno, string $errorString): void
	{
		self::$unserialize_exception = new SyntaxException($errorString, [], $errno);
	}

	/**
	 * Safe unserialize function.
	 *
	 * NOT THREAD SAFE!
	 *
	 * @param string $serialized
	 * @return mixed
	 * @throws SyntaxException
	 */
	public static function unserialize(string $serialized): mixed
	{
		self::$unserialize_exception = null;
		set_error_handler([__CLASS__, '_unserialize_handler', ]);
		$original = unserialize($serialized);
		restore_error_handler();
		if (self::$unserialize_exception) {
			$exception = self::$unserialize_exception;
			self::$unserialize_exception = null;

			throw $exception;
		}
		return $original;
	}

	/**
	 * Test PHP for presence of various features
	 *
	 * @param string|array $features
	 * @param bool $throw Unsupported will be thrown if fails
	 * @return array
	 * @throws
	 */
	public static function requires(string|array $features, bool $throw = false): array
	{
		$features = Types::toList($features);
		$results = [];
		$errors = [];
		foreach ($features as $feature) {
			switch ($feature) {
				case self::FEATURE_PROCESS_CONTROL:
					$results[$feature] = $result = function_exists('pcntl_exec');
					if (!$result) {
						$errors[] = ArrayTools::map("Need pcntl extensions for PHP\nphp.ini at {0}\n", [get_cfg_var('cfg_file_path')]);
					}

					break;
				case self::FEATURE_TIME_LIMIT:
					$results[$feature] = $result = !Types::toBool(ini_get('safe_mode'));
					if (!$result) {
						$errors[] = ArrayTools::map("PHP safe mode prevents removing time limits on pages\nphp.ini at {0}\n", [get_cfg_var('safe_mode')]);
					}

					break;
				case self::FEATURE_POSIX:
					$results[$feature] = $result = function_exists('posix_getpid');
					if (!$result) {
						$errors[] = 'Need POSIX extensions to PHP (posix_getpid)';
					}

					break;
				default:
					$results[$feature] = false;
					$errors[] = "Unknown feature \"$feature\"";

					break;
			}
		}
		if (count($errors) > 0) {
			if ($throw) {
				throw new UnsupportedException('Required features are missing {errors}', ['errors' => $errors]);
			}
		}
		return $results;
	}

	/**
	 * Set a PHP feature. Useful when you're a moron and can't remember the ini file settings names, or
	 * if best practices change over time. Wink, wink.
	 *
	 * @param string $feature
	 * @param int|float|string $value Value to set it to
	 *
	 * @return mixed Return previous value
	 * @throws UnimplementedException
	 */
	public static function setFeature(string $feature, int|float|string $value): mixed
	{
		$feature = strtolower($feature);
		switch ($feature) {
			case self::FEATURE_TIME_LIMIT:
				$old_value = ini_get('max_execution_time');
				if (!set_time_limit(intval($value))) {
					throw new UnimplementedException('set_time_limit failed');
				}
				return $old_value;
			case self::FEATURE_MEMORY_LIMIT:
				$old_value = Types::toBytes(ini_get('memory_limit'));
				ini_set('memory_limit', strval(Types::toBytes($value))); // TODO 8.1 PHP accepts float
				return $old_value;
			default:
				throw new UnimplementedException('No such feature {feature}', ['feature' => $feature]);
		}
	}

	/**
	 * Is this a valid function name, syntactically?
	 *
	 * @param string $func
	 * @return bool
	 */
	public static function validFunction(string $func): bool
	{
		return self::cleanFunction($func) === $func;
	}

	/**
	 * Is this a valid class name, syntactically?
	 *
	 * @param string $class
	 * @return boolean
	 */
	public static function validClass(string $class): bool
	{
		return self::cleanClass($class) === $class;
	}

	/**
	 * Convert a string into a valid PHP function name.
	 * Useful for cleaning hooks generated automatically or
	 * from user input.
	 *
	 * @param string $func
	 *            String to clean
	 * @return string
	 */
	public static function cleanFunction(string $func): string
	{
		return preg_replace('/[^a-zA-Z0-9_]/', '_', $func);
	}

	/**
	 * Remove any unwanted characters from a class name; does not validate
	 * class names which start with invalid characters.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function cleanClass(string $name): string
	{
		return preg_replace('/[^a-zA-Z0-9_\\\\]/', '_', $name);
	}

	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value
	 * @return mixed
	 */

	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value
	 * @param boolean $throw Throw an ParseException error when value is invalid JSON. Defaults to true.
	 * @return mixed
	 * @throws ParseException
	 * @deprecated 2023-06
	 */
	public static function autoType(mixed $value, bool $throw = true): mixed
	{
		return Types::autoType($value, $throw);
	}

	/**
	 * Given a class with a namespace, return just the class portion.
	 *
	 * e.g. PHP::parseClass("zesk\Dude") === "Dude"
	 *
	 * As of November 2018, does not appear that PHP have a native function which does this.
	 *
	 * @param string $class
	 * @return string
	 */
	public static function parseClass(string $class): string
	{
		$parts = self::parseNamespaceClass($class);
		return $parts[1];
	}

	/**
	 * Given a class with a namespace, return just the class portion.
	 *
	 * e.g. PHP::parseClass("zesk\Dude") === "Dude"
	 *
	 * @param string $class
	 * @return string
	 */
	public static function parseNamespace(string $class): string
	{
		$parts = self::parseNamespaceClass($class);
		return $parts[0];
	}

	/**
	 * Given a class with a namespace, return a two-element list with the namespace first and the class second.
	 *
	 * Returns "" for namespace if no namespace
	 *
	 * @param string $class
	 * @return string[]
	 */
	public static function parseNamespaceClass(string $class): array
	{
		return StringTools::reversePair($class, '\\', '', $class);
	}

	/**
	 *
	 * @param Throwable|string $message
	 * @param array $arguments
	 */
	public static function log(Throwable|string $message, array $arguments = []): void
	{
		if ($message instanceof Throwable) {
			$arguments = Exception::exceptionVariables($message) + $arguments;
			$message = "{class}: {message} at {file}:{line}\nBacktrace: {backtrace}";
		}
		error_log(ArrayTools::map($message, $arguments));
	}

	private static array $included = [];

	/**
	 * @param string $path
	 * @param array $listOptions
	 * @return bool true if include happened, false if already included
	 * @throws DirectoryNotFound
	 * @throws ParameterException
	 */
	public static function includePath(string $path, array $listOptions = []): bool
	{
		$path = Directory::must(realpath($path));
		if (self::$included[$path] ?? null) {
			return false;
		}
		self::$included[$path] = true;
		foreach (Directory::listRecursive($path, $listOptions + [
			Directory::LIST_RULE_DIRECTORY_WALK => [
				"/\/./" => false, "/\/vendor\//" => false, true,
			], Directory::LIST_RULE_FILE => [
				"/\.php$/" => true, false,
			],
		] + $listOptions + [
			Directory::LIST_RULE_DIRECTORY => false, Directory::LIST_ADD_PATH => true,
		]) as $phpFilePath) {
			require_once "$phpFilePath";
		}
		return true;
	}
}
