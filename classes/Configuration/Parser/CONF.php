<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Configuration
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Configuration_Parser_CONF extends Configuration_Parser {
	public const SEPARATOR_DEFAULT = '=';

	public const UNQUOTE_DEFAULT = '\'\'""';

	protected array $options = [
		'overwrite' => true,
		'trim_key' => true,
		'separator' => self::SEPARATOR_DEFAULT,
		'trim_value' => true,
		'autotype' => true,
		'lower' => true,
		'multiline' => true,
		'unquote' => self::UNQUOTE_DEFAULT,
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
	 * {@inheritDoc}
	 * @see \zesk\Configuration_Parser::editor()
	 */
	public function editor(string $content = '', array $options = []): Configuration_Editor {
		return new Configuration_Editor_CONF($content, $options);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @return Interface_Settings
	 * @see \zesk\Configuration_Parser::process()
	 */
	public function process(): void {
		$autotype = toBool($this->options['autotype'] ?? false);
		$unquote = strval($this->options['unquote'] ?? self::UNQUOTE_DEFAULT);
		$multiline = toBool($this->options['multiline'] ?? false);
		$overwrite = toBool($this->options['overwrite'] ?? false);
		$settings = $this->settings;
		$dependency = $this->dependency;

		$lines = explode("\n", $this->content);
		if ($multiline) {
			$lines = self::joinLines($lines);
		}
		if ($dependency) {
			$this->dependency->push($this->option('name', 'unnamed-' . get_class($this)));
		}
		$lower = $this->option('lower');
		foreach ($lines as $line) {
			$parse_result = $this->parse_line($line);
			if ($parse_result === null) {
				continue;
			}
			$append = false;
			[$key, $value] = $parse_result;
			/**
			 * Parse and normalize key
			 */
			if (str_ends_with($key, '[]')) {
				$key = StringTools::unsuffix($key, '[]');
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
				$value = unquote($value, $unquote, $found_quote);
			}
			$dependencies = [];
			if ($found_quote !== '\'') {
				$value = bash::substitute($value, $settings, $dependencies, $lower);
			}
			if (!$found_quote) {
				if ($autotype) {
					$value = PHP::autotype($value);
				}
			}

			/**
			 * Now apply to back to our settings, or handle special values
			 */
			if ($this->loader && strtolower($key) === 'include') {
				$this->handleInclude($value);
			} elseif ($append) {
				$append_value = toArray($settings->get($key));
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
			return;
		} else {
			$path = dirname($this->loader->current());
			$this->loader->appendFiles([path($path, $file)]);
		}
	}

	/**
	 * Allow multi-line settings by placing additional lines beginning with whitespace
	 *
	 * @param array $lines
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
	public function parse_line(string $line): ?array {
		$separator = $this->options['separator'] ?? '=';
		$line = trim($line);
		if (str_starts_with($line, '#')) {
			return null;
		}
		$matches = false;
		if (preg_match('/^export\s+/', $line, $matches)) {
			$line = substr($line, strlen($matches[0]));
		}
		[$key, $value] = pair($line, $separator);
		if (!$key) {
			return null;
		}
		if ($this->options['trim_key'] ?? false) {
			$key = trim($key);
		}
		if ($this->options['trim_value'] ?? false) {
			$value = trim($value);
		}
		if ($this->options['lower'] ?? false) {
			$key = strtolower($key);
		}
		return [
			$key,
			$value,
		];
	}
}
