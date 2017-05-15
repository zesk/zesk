<?php
namespace zesk;

class Parse_Log extends Hookable {
	
	/**
	 * Integer pattern
	 *
	 * @var string
	 */
	const pattern_integer = '[0-9]+';
	
	/**
	 * TLD pattern
	 *
	 * @var string
	 */
	const pattern_domain = '[-_.a-zA-Z]+';
	
	/**
	 * IP Address pattern
	 *
	 * @var string
	 */
	const pattern_ip = '(?:\d{1,3}\.){3}\d{1,3}';
	
	/**
	 * Double-quoted string pattern
	 *
	 * @var string
	 */
	const pattern_dquoted_string = '"[^"]*"';
	
	/**
	 * Number pattern
	 *
	 * @var string
	 */
	const pattern_number = '[0-9]+';
	
	/**
	 * Skip pattern (assumes space delimited)
	 *
	 * @var string
	 */
	const pattern_skip = '[^ ]+';
	
	/**
	 * YMD pattern
	 *
	 * @var string
	 */
	const pattern_date_ymd = '2[0-9][0-9][0-9]-[01][0-9]-[0-3][0-9]';
	
	/**
	 * HMS pattern
	 *
	 * @var string
	 */
	const pattern_time_hms = '[0-2][0-9]:[0-5][0-9]:[0-5][0-9]';
	
	/**
	 * Known patterns
	 *
	 * @return array
	 */
	protected function patterns() {
		return array(
			'domain' => self::pattern_domain,
			'port' => self::pattern_integer,
			'ip' => self::pattern_ip,
			'*' => self::pattern_skip,
			'skip' => self::pattern_skip,
			'size' => array(
				"pattern" => self::pattern_number,
				"function" => __CLASS__ . '::to_integer'
			),
			'yyyy-mm-dd' => self::pattern_date_ymd,
			'hh-mm-ss' => self::pattern_time_hms,
			'mmm' => array(
				'pattern' => '[A-Za-z]{3}',
				'postprocess' => 'Parse_Log::month_to_number;Parse_Log::implied_year'
			),
			'mm' => array(
				'pattern' => '(?:0[1-9]|1[0-2])',
				'postprocess' => 'Parse_Log::implied_year'
			),
			'm' => array(
				'pattern' => '(?:[1-9]|1[0-2])',
				'postprocess' => 'Parse_Log::implied_year'
			),
			'dd' => array(
				'pattern' => '(?:0[1-9]|[12][0-9]|3[01])',
				'postprocess' => 'Parse_Log::implied_year'
			),
			'd' => array(
				'pattern' => '(?:[1-9]|[12][0-9]|3[01])',
				'postprocess' => 'Parse_Log::implied_year'
			),
			'yyyy' => array(
				'pattern' => '20[0-9][0-9]'
			),
			'DD' => '[0-9]{2}'
		);
	}
	
	/**
	 * Create a log parser
	 *
	 * @param string $type
	 * @return Parse_Log
	 */
	final public static function factory($type, array $options = array()) {
		return zesk()->objects->factory(__CLASS__ . '_' . $type, $options);
	}
	
	/**
	 * Raw line format with pattern placeholders
	 *
	 * @var string
	 */
	private $format = null;
	
	/**
	 * Converted line pattern
	 *
	 * @var string
	 */
	private $line_pattern = null;
	
	/**
	 * Array of index => variable name
	 *
	 * @var array
	 */
	private $line_captures = null;
	
	/**
	 * Array of convert functions
	 *
	 * @var string
	 */
	private $capture_functions = null;
	
	/**
	 * Array of additional names for a variable
	 *
	 * @var string
	 */
	private $capture_synonyms = null;
	
	/**
	 * Array of postprocessing functions
	 *
	 * @var string
	 */
	private $capture_postprocess = null;
	
	/**
	 */
	private function _init() {
		if ($this->line_pattern !== null) {
			return;
		}
		$this->format = $format = $this->call_hook_arguments('format', array(), $this->option('format'));
		$match = null;
		$captures = array(
			"line"
		);
		$patterns = array_change_key_case($this->patterns());
		$functions = array();
		$line_pattern = "";
		$capture_postprocess = array();
		$capture_synonyms = array();
		$last_offset = 0;
		while (preg_match('/\{([^}]+)\}/', $format, $match, PREG_OFFSET_CAPTURE, $last_offset) !== 0) {
			$token = $match[0][0];
			$offset = $match[0][1];
			$name = strtolower($match[1][0]);
			$codename = strtr($name, "-", "_");
			if (!array_key_exists($name, $patterns)) {
				zesk()->logger->warning("No pattern {name} exists in patterns for {class}: {available_patterns}", array(
					'name' => $name,
					'class' => get_class($this),
					'available_patterns' => array_keys($patterns)
				));
				$pattern_data = array(
					'pattern' => self::pattern_skip
				);
			} else {
				$pattern_data = $patterns[$name];
				if (is_string($pattern_data)) {
					$pattern_data = array(
						'pattern' => $pattern_data
					);
				} else if (!is_array($pattern_data)) {
					zesk()->logger->error("Pattern {name} does not have correct internal syntax (array or string) {class}: {type}", array(
						'name' => $name,
						'class' => get_class($this),
						'type' => gettype($pattern_data)
					));
					$pattern_data = array(
						'pattern' => self::pattern_skip
					);
				}
			}
			// Append this token
			$line_pattern .= substr($format, $last_offset, $offset - $last_offset) . '(' . $pattern_data['pattern'] . ')';
			$functions[count($captures)] = avalue($pattern_data, 'function', null);
			$synonyms = avalue($pattern_data, 'synonyms', null);
			if ($synonyms) {
				$capture_synonyms[$codename] = to_list($synonyms);
			}
			$postprocesses = avalue($pattern_data, 'postprocess', null);
			if ($postprocesses) {
				$postprocesses = to_list($postprocesses);
				foreach ($postprocesses as $postprocess) {
					$capture_postprocess[serialize($postprocess)] = $postprocess;
				}
			}
			$captures[] = $codename;
			foreach (avalue($pattern_data, 'captures', array()) as $capture) {
				$captures[] = strtr($capture, "-", "_");
			}
			$last_offset = $offset + strlen($token);
		}
		$line_pattern .= substr($format, $last_offset);
		$this->line_pattern = "#$line_pattern#";
		$this->line_captures = $captures;
		$this->capture_functions = arr::clean($functions, null);
		$this->capture_postprocess = array_values($capture_postprocess);
		$this->capture_synonyms = $capture_synonyms;
	}
	
	/**
	 * Parse a line
	 *
	 * @param string $line
	 * @throws Exception_Syntax
	 * @return array
	 */
	final public function line($line) {
		$this->_init();
		$matches = null;
		if (!preg_match($this->line_pattern, $line, $matches)) {
			throw new Exception_Syntax("Line {line} does not match line pattern {line_pattern}", array(
				"line" => $line,
				"line_pattern" => $this->line_pattern,
				"class" => get_class($this)
			));
		}
		foreach ($this->capture_functions as $index => $function) {
			$matches[$index] = call_user_func($function, $matches[$index]);
		}
		$matches = array_change_key_case(arr::map_keys($matches, $this->line_captures));
		foreach ($this->capture_synonyms as $name => $synonyms) {
			if (!array_key_exists($name, $matches)) {
				continue;
			}
			foreach ($synonyms as $synonym) {
				$matches[$synonym] = $matches[$name];
			}
		}
		foreach ($this->capture_postprocess as $method) {
			$matches = call_user_func($method, $matches);
		}
		$matches = $this->imply_variables($matches);
		return $this->call_hook_arguments("line_alter", array(
			$matches
		), $matches);
	}
	
	/**
	 * Imply missing variables
	 *
	 * @param array $variables
	 * @return array
	 */
	private function imply_variables(array $variables) {
		foreach (to_list('m;d;h;s;n') as $k) {
			$kk = $k . $k;
			if (!array_key_exists($kk, $variables) && array_key_exists($k, $variables)) {
				$variables[$kk] = Text::ralign($variables[$k], 2, '0');
			}
			if (!array_key_exists($k, $variables) && array_key_exists($kk, $variables)) {
				$variables[$k] = intval($variables[$kk]);
			}
		}
		if (!array_key_exists('timestamp', $variables)) {
			extract($variables, EXTR_IF_EXISTS);
			$variables['timestamp'] = strtotime(map('{yyyy}-{mm}-{dd} {hh-mm-ss}', $variables));
		}
		return $variables;
	}
	public static function strtotime($value) {
		return strtotime(unquote($value, "\"\"''[]{}()"));
	}
	public static function unquote($value) {
		return unquote($value, "\"\"''[]{}()");
	}
	public static function to_integer($value) {
		return to_integer($value, $value);
	}
	
	/*
	 * Conversion functions
	 */
	final public static function month_to_number(array $vars) {
		$mmm = $vars['mmm'];
		$timestamp = strtotime("$mmm 1, 2000 00:00:00");
		if (!array_key_exists('mm', $vars)) {
			$vars['mm'] = date('m', $timestamp);
		}
		return $vars;
	}
	
	/**
	 * Implied year - guesses log entries without a year based on the current date
	 *
	 * @param array $variables
	 * @return unknown number
	 */
	public function implied_year(array $variables) {
		// Allow for December dates to be last year in the first month of the next year
		if (array_key_exists('yyyy', $variables)) {
			return $variables;
		}
		$variables['yyyy'] = date('Y');
		$m = $d = null;
		extract($variables, EXTR_IF_EXISTS);
		if ($m !== null) {
			if (intval($m) === 12 && intval(date('n')) === 1) {
				$variables['yyyy'] -= 1;
			}
		}
		return $variables;
	}
}
