<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Configuration
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Configuration\Parser;

use zesk\bash;
use zesk\Configuration\Editor;
use zesk\Configuration\Parser;

use zesk\Configuration\Editor\CONFEditor;
use zesk\Directory;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\File;
use zesk\PHP;
use zesk\StringTools;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class CONF extends Parser {
	public const OPTION_AUTO_TYPE = 'autoType';

	public const OPTION_UNQUOTE = 'unquote';

	public const OPTION_MULTILINE = 'multiline';

	public const OPTION_OVERWRITE = 'overwrite';

	public const OPTION_TRIM_KEY = 'trimKey';

	public const OPTION_TRIM_VALUE = 'trimValue';

	public const OPTION_LOWER = 'lower';

	public const OPTION_NAME = 'name';

	public const OPTION_SEPARATOR = 'separator';

	public const DEFAULT_AUTO_TYPE = true;

	public const DEFAULT_OVERWRITE = true;

	public const DEFAULT_TRIM_KEY = true;

	public const DEFAULT_TRIM_VALUE = true;

	public const DEFAULT_LOWER = false;

	public const DEFAULT_SEPARATOR = '=';

	public const DEFAULT_MULTILINE = true;

	public const DEFAULT_UNQUOTE = '\'\'""';

	protected array $options = [
		self::OPTION_OVERWRITE => self::DEFAULT_OVERWRITE,
		self::OPTION_TRIM_KEY => self::DEFAULT_TRIM_KEY,
		self::OPTION_SEPARATOR => self::DEFAULT_SEPARATOR,
		self::OPTION_TRIM_VALUE => self::DEFAULT_TRIM_VALUE,
		self::OPTION_AUTO_TYPE => self::DEFAULT_AUTO_TYPE,
		self::OPTION_LOWER => self::DEFAULT_LOWER,
		self::OPTION_MULTILINE => self::DEFAULT_MULTILINE,
		self::OPTION_UNQUOTE => self::DEFAULT_UNQUOTE,
	];

	/**
	 */
	public function initialize(): void {
	}

	/**
	 */
	public function validate(): bool {
		return true;
	}

	/**
	 *
	 * @see Parser::editor
	 */
	public function editor(string $content = '', array $options = []): Editor {
		return new CONFEditor($content, $options);
	}

	/**
	 *
	 * @return void
	 * @throws Semantics
	 * @throws ParseException
	 * @see Parser::process
	 */
	public function process(): void {
		$autoType = Types::toBool($this->options[self::OPTION_AUTO_TYPE]);
		$unquote = strval($this->options[self::OPTION_UNQUOTE]);
		$multiline = Types::toBool($this->options[self::OPTION_MULTILINE]);
		$overwrite = Types::toBool($this->options[self::OPTION_OVERWRITE]);
		$settings = $this->settings;
		$dependency = $this->dependency;

		$lines = explode("\n", $this->content);
		if ($multiline) {
			$lines = self::joinLines($lines);
		}
		if ($dependency) {
			$this->dependency->push($this->option(self::OPTION_NAME, 'unnamed-' . get_class($this)));
		}
		$lower = $this->optionBool(self::OPTION_LOWER, self::DEFAULT_LOWER);
		foreach ($lines as $line) {
			$parse_result = $this->parseLine($line);
			if ($parse_result === null) {
				continue;
			}
			$append = false;
			[$key, $value] = $parse_result;
			/**
			 * Parse and normalize key
			 */
			if (str_ends_with($key, '[]')) {
				$key = StringTools::removeSuffix($key, '[]');
				$append = true;
			}
			$found_quote = null;
			$key = strtr($key, [
				'___' => '\\',
				'__' => '::',
			]);
			/**
			 * Parse and normalize value
			 */
			$found_quote = '';
			if ($unquote) {
				$value = StringTools::unquote($value, $unquote, $found_quote);
			}
			$dependencies = [];
			if ($found_quote !== '\'') {
				$value = bash::substitute($value, $settings, $dependencies, $lower);
			}
			if (!$found_quote) {
				if ($autoType) {
					$value = Types::autoType($value);
				}
			}

			/**
			 * Now apply to back to our settings, or handle special values
			 */
			if ($this->loader && strtolower($key) === 'include') {
				$this->handleInclude($value);
			} elseif ($append) {
				$append_value = Types::toArray($settings->get($key));
				$append_value[] = $value;
				$settings->set($key, $append_value);
				$dependency?->defines($key, array_keys($dependencies));
			} else {
				if ($overwrite || !$settings->has($key)) {
					$settings->set($key, $value);
					$dependency?->defines($key, array_keys($dependencies));
				}
			}
		}
		$dependency?->pop();
	}

	/**
	 * Handle include files specially
	 *
	 * @param string $file
	 *            Name of additional include file
	 */
	private function handleInclude(string $file): void {
		if (File::isAbsolute($file)) {
			$this->loader->appendFiles([
				$file,
			]);
		} else {
			$path = dirname($this->loader->current());
			$this->loader->appendFiles([Directory::path($path, $file)]);
		}
	}

	/**
	 * Allow multi-line settings by placing additional lines beginning with whitespace
	 *
	 * @param array $lines
	 * @return array
	 */
	private static function joinLines(array $lines): array {
		$result = [
			array_shift($lines),
		];
		$last = 0;
		foreach ($lines as $line) {
			if (in_array(substr($line, 0, 1), [
				"\t",
				' ',
			])) {
				$result[$last] .= "\n$line";
			} else {
				$result[] = $line;
				$last++;
			}
		}
		return $result;
	}

	/**
	 * Shared by Configuration_Editor_CONF
	 *
	 * @param string $line
	 * @return ?array
	 */
	public function parseLine(string $line): ?array {
		$separator = $this->options[self::OPTION_SEPARATOR];
		$line = trim($line);
		if (str_starts_with($line, '#')) {
			return null;
		}
		$matches = false;
		if (preg_match('/^export\s+/', $line, $matches)) {
			$line = substr($line, strlen($matches[0]));
		}
		[$key, $value] = StringTools::pair($line, $separator);
		if (!$key) {
			return null;
		}
		if ($this->options[self::OPTION_TRIM_KEY]) {
			$key = trim($key);
		}
		if ($this->options[self::OPTION_TRIM_VALUE]) {
			$value = trim($value);
		}
		if ($this->options[self::OPTION_LOWER]) {
			$key = strtolower($key);
		}
		return [
			$key,
			$value,
		];
	}
}
