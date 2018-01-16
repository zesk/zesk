<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Configuration_Parser_CONF extends Configuration_Parser {
	protected $options = array(
		"overwrite" => true,
		"trim_key" => true,
		"separator" => '=',
		"trim_value" => true,
		"autotype" => true,
		"lower" => true,
		"multiline" => true,
		"unquote" => '\'\'""'
	);

	/**
	 */
	public function initialize() {
	}

	/**
	 */
	public function validate() {
		return true;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Configuration_Parser::editor()
	 */
	public function editor($content = null, array $options = array()) {
		return new Configuration_Editor_CONF($content, $options);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Configuration_Parser::process()
	 * @return Interface_Settings
	 */
	public function process() {
		$separator = $lower = $trim_key = $unquote = $trim_value = $autotype = $overwrite = $multiline = null;
		extract($this->options, EXTR_IF_EXISTS);

		$settings = $this->settings;
		$dependency = $this->dependency;

		$lines = explode("\n", $this->content);
		if ($multiline) {
			$lines = self::join_lines($lines);
		}
		foreach ($lines as $line) {
			$parse_result = self::parse_line($line);
			if ($parse_result === null) {
				continue;
			}
			$append = false;
			list($key, $value) = $parse_result;
			/**
			 * Parse and normalize key
			 */
			if (ends($key, '[]')) {
				$key = str::unsuffix($key, "[]");
				$append = true;
			}
			$found_quote = null;
			$key = strtr($key, array(
				"___" => "\\",
				"__" => "::"
			));
			/**
			 * Parse and normalize value
			 */
			if ($unquote) {
				$value = unquote($value, $unquote, $found_quote);
			}
			$dependencies = array();
			if ($found_quote !== "'") {
				$value = bash::substitute($value, $settings, $dependencies);
			}
			if (!$found_quote) {
				if ($autotype) {
					$value = PHP::autotype($value);
				}
			}

			/**
			 * Now apply to back to our settings, or handle special values
			 */
			if ($this->loader && strtolower($key) === "include") {
				$this->handle_include($value);
			} else if ($append) {
				$append_value = to_array($settings->get($key));
				$append_value[] = $value;
				$settings->set($key, $append_value);
				if ($dependency) {
					$dependency->defines($key, array_keys($dependencies));
				}
			} else {
				if ($overwrite || !$settings->has($key)) {
					$settings->set($key, $value);
					if ($dependency) {
						$dependency->defines($key, array_keys($dependencies));
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * Handle include files specially
	 *
	 * @param string $file
	 *        	Name of additional include file
	 */
	private function handle_include($file) {
		if (File::is_absolute($file)) {
			$this->loader->append_files(array(
				$file
			));
			return;
		}
		$files = $missing = array();
		$path = dirname($this->loader->current());
		$conf_path = path($path, $file);
		if (file_exists($conf_path)) {
			$files[] = $conf_path;
		} else {
			$missing[] = $conf_path;
		}
		$this->loader->append_files($files, $missing);
	}

	/**
	 * Allow multi-line settings by placing additional lines beginning with whitespace
	 *
	 * @param array $lines
	 */
	private static function join_lines(array $lines) {
		$result = array(
			array_shift($lines)
		);
		$last = 0;
		foreach ($lines as $line) {
			if (in_array(substr($line, 0, 1), array(
				"\t",
				" "
			))) {
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
	 * @return array
	 */
	public function parse_line($line) {
		$separator = $trim_key = $trim_value = $lower = null;
		extract($this->options, EXTR_IF_EXISTS);

		$line = trim($line);
		if (substr($line, 0, 1) == "#") {
			return null;
		}
		$matches = false;
		if (preg_match('/^export\s+/', $line, $matches)) {
			$line = substr($line, strlen($matches[0]));
		}
		list($key, $value) = pair($line, $separator, null, null);
		if (!$key) {
			return null;
		}
		if ($trim_key) {
			$key = trim($key);
		}
		if ($trim_value) {
			$value = trim($value);
		}
		if ($lower) {
			$key = strtolower($key);
		}
		return array(
			$key,
			$value
		);
	}
}
