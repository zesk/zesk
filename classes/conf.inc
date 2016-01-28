<?php
/**
 * Configuration files, somewhat compatible with BASH environment shells
 * Useful for setting options which you want to also access via BASH or compatible shell.
 * @see conf::load
 *
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
class conf {
	
	/**
	 * Load a global environment file which supports a variety of formats.
	 *
	 * Including mimicing the BASH set syntax
	 *
	 * @param string $file
	 *        	A file to load
	 * @param mixed $options
	 *        	Typically, an array of options while loading true to overwite existing settings
	 *        	with new ones, or array
	 *        	containing options:
	 *        	'overwrite' => boolean Overwrite existing entries with these
	 *        	'trim_key' => boolean Trim key whitespace
	 *        	'trim_value' => boolean Trim value whitespace when unquoted
	 *        	'autotype' => boolean Use hints to determine type when unquoted
	 *        	
	 *        	Figures out if it's a boolean or integer, may add serialized value if needed
	 *        	later.
	 *        	
	 * @throws Exception_File_NotFound
	 */
	public static function load($file, $options = array()) {
		if (zesk::getb('conf::debug')) {
			log::debug("conf::load({file})", array(
				"file" => $file
			));
		}
		return self::parse(file::lines($file), $options);
	}
	private static $options_default_global = null;
	
	/**
	 * Default options for configuration file loading
	 *
	 * Inherit from globals with the name conf::name, for example, like:
	 *
	 * zesk::set("conf::overwrite", true);
	 *
	 * will make the default for configuration file loading "overwrite"
	 *
	 * @return array
	 */
	private static function _options_default_global() {
		if (self::$options_default_global === null) {
			$prefix = __CLASS__ . "::";
			self::$options_default_global = arr::kunprefix(zesk::get(array(
				$prefix . "overwrite" => false,
				$prefix . "trim_key" => true,
				$prefix . "separator" => '=',
				$prefix . "trim_value" => true,
				$prefix . "autotype" => true,
				$prefix . "lower" => true,
				$prefix . "multiline" => false,
				$prefix . "unquote" => '\'\'""',
				$prefix . "variables" => null
			)), $prefix);
		}
		return self::$options_default_global;
	}
	
	/**
	 *
	 * @param array $options        	
	 */
	public static function options_default(array $options = array()) {
		$options += self::_options_default_global();
		if (!is_array($options['variables'])) {
			$options['variables'] = arr::kflatten(zesk::get(), "::");
		}
		$options['variables'] = array_change_key_case($options['variables']);
		return $options;
	}
	
	/**
	 * The meat and potatoes of config file parsing.
	 *
	 * @param array $lines        	
	 * @param array $options        	
	 * @return array loaded values
	 */
	public static function parse(array $lines, $options = array()) {
		if ($options === true) {
			$options = array(
				'overwrite' => true
			);
		}
		/**
		 * Parsing options
		 *
		 * @var array
		 */
		$options = to_array($options, array());
		
		$options = self::options_default($options);
		$separator = $lower = $trim_key = $unquote = $trim_value = $autotype = $overwrite = $variables = $multiline = null;
		extract($options, EXTR_IF_EXISTS);
		
		if ($multiline) {
			$lines = self::join_lines($lines);
		}
		
		$result = array();
		foreach ($lines as $line) {
			$parse_result = self::parse_line($line, array(
				'lower' => $lower,
				'trim_key' => $trim_key,
				'trim_value' => $trim_value,
				'separator' => $separator
			));
			if ($parse_result === null) {
				continue;
			}
			$append = false;
			list($key, $value) = $parse_result;
			if (ends($key, '[]')) {
				$key = str::unsuffix($key, "[]");
				$append = true;
			}
			$found_quote = null;
			if ($unquote) {
				$value = unquote($value, $unquote, $found_quote);
			}
			if ($found_quote !== "'") {
				$value = zesk\bash::substitute($value, $variables);
			}
			if (!$found_quote) {
				if ($autotype) {
					$value = zesk::autotype($value);
				}
			}
			if ($append) {
				arr::append($result, $key, $value);
				arr::append($variables, strtolower($key), $value);
			} else {
				if ($overwrite || !array_key_exists($key, $result)) {
					$result[$key] = $value;
				}
				if ($overwrite || !array_key_exists($key, $variables)) {
					$variables[strtolower($key)] = $value;
				}
			}
		}
		return $result;
	}
	static function load_inherit($files, array $paths, array $options = array()) {
		$files = to_list($files);
		$options = self::options_default($options);
		$overwrite = to_bool(avalue($options, 'overwrite', zesk::getb('conf::overwrite')));
		$result = array();
		$file_paths = array();
		$no_file_paths = array();
		foreach ($files as $file) {
			if (file::is_absolute($file)) {
				if (is_readable($file)) {
					$file_paths[] = $file;
				} else {
					$no_file_paths[] = $file;
				}
				continue;
			}
			foreach ($paths as $path) {
				if (!is_dir($path)) {
					continue;
				}
				$ff = path($path, $file);
				if (is_readable($ff)) {
					$file_paths[] = $ff;
				} else {
					$no_file_paths[] = $ff;
				}
			}
		}
		foreach ($file_paths as $ff) {
			$path_result = self::load($ff, $options);
			if ($overwrite) {
				$result = $path_result + $result;
				$options['variables'] = $path_result + $options['variables'];
			} else {
				$result += $path_result;
				$options['variables'] += $path_result;
			}
		}
		zesk::add('conf::loaded', $file_paths);
		zesk::add('conf::load_no_file', $no_file_paths);
		return $result;
	}
	public static function globals($paths, $options = array()) {
		if ($options === true) {
			$options = array(
				'overwrite' => true
			);
		}
		$paths = to_list($paths);
		$options = to_array($options, array());
		$files = avalue($options, 'files', zesk::get('conf::files', 'zesk.conf;environment.sh'));
		$result = conf::load_inherit($files, $paths, $options);
		zesk::set($result);
		return $result;
	}
	
	/**
	 * Save changes to a configuration file
	 *
	 * @param unknown $path        	
	 * @param array $edits        	
	 * @param array $options        	
	 */
	public static function edit($path, array $edits, array $options = array()) {
		$options = self::options_default($options);
		$low_edits = arr::flip_copy(array_keys($edits), true);
		$new_lines = array();
		if (file_exists($path)) {
			$lines = file::lines($path);
			foreach ($lines as $line) {
				$result = self::parse_line($line, $options);
				if ($result === null) {
					$new_lines[] = rtrim($line, "\n") . "\n";
				} else {
					list($key, $value) = $result;
					$lowkey = strtolower($key);
					if (array_key_exists($lowkey, $low_edits)) {
						$key = $low_edits[$lowkey];
						unset($low_edits[$lowkey]);
						$new_lines[] = $key . '=' . text::lines_wrap(json::encode($edits[$key]), "\t", "", "") . "\n";
						unset($edits[$key]);
					} else {
						$new_lines[] = rtrim($line, "\n") . "\n";
					}
				}
			}
		}
		foreach ($low_edits as $low_edit => $key) {
			$new_lines[] = $key . '=' . json::encode($edits[$key]) . "\n";
		}
		file::put($path, implode("", $new_lines));
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
	private static function parse_line($line, array $options) {
		$separator = $trim_key = $trim_value = $lower = null;
		extract($options, EXTR_IF_EXISTS);
		
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
