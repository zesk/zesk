<?php declare(strict_types=1);

/**
 * @copyright &copy;
 */
namespace zesk;

class PHP_Inspector {
	/**
	 *
	 * @var Application
	 */
	private $app = null;

	/**
	 * File path
	 *
	 * @var string
	 */
	protected $file = null;

	/**
	 * PHP File contents
	 *
	 * @var string
	 */
	protected $contents = null;

	/**
	 *
	 * @var array
	 */
	protected $tokens = null;

	/**
	 *
	 * @var integer
	 */
	protected $tokens_length = null;

	/**
	 *
	 * @var string[]
	 */
	protected $classes = null;

	/**
	 *
	 * @var string[]
	 */
	protected $functions = null;

	/**
	 *
	 * @param Application $application
	 * @param string $file
	 *        	File to inspect
	 * @return \zesk\PHP_Inspector
	 */
	public static function factory(Application $application, $file) {
		return $application->factory(__CLASS__, $application, $file);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $file
	 */
	public function __construct(Application $application, $file) {
		File::depends($file);
		$this->app = $application;
		$this->file = $file;
		$this->contents = file_get_contents($file);
		$this->tokens = token_get_all($this->contents);
		$this->tokens_length = count($this->tokens);

		$this->classes = null;
		$this->functions = null;
		$this->included = false;
	}

	/**
	 * Determine declared classes in this file
	 */
	public function defined_classes() {
		if ($this->classes === null) {
			$this->classes = $this->_compute_classes();
		}
		return $this->classes;
	}

	/**
	 *
	 * @param string $class
	 * @throws Exception_Class_NotFound
	 * @return \ReflectionClass
	 */
	public function reflection_class($class) {
		if (!in_array($class, $this->defined_classes())) {
			throw new Exception_Class_NotFound($class, 'Class {class} is not defined in file {file}', [
				'class' => $class,
				'file' => $this->file,
			]);
		}
		if (!$this->included) {
			require_once $this->file;
			$this->included = true;
		}
		if (!class_exists($class, false)) {
			throw new Exception_Class_NotFound($class, 'Class {class} is not defined in file {file} after include', [
				'class' => $class,
				'file' => $this->file,
			]);
		}
		return new \ReflectionClass($class);
	}

	/**
	 * Determine declared top-level functions in this file
	 *
	 * @return string[]
	 */
	public function defined_functions() {
		if ($this->functions === null) {
			$this->functions = $this->_compute_functions();
		}
		return $this->functions;
	}

	/**
	 *
	 * @param integer $index
	 * @return array
	 */
	private function token($index) {
		$token = $this->tokens[$index];
		return is_string($token) ? [
			$token,
			$token,
		] : $token;
	}

	/**
	 * Walk the token list and extract class names
	 *
	 * @return string[]
	 */
	private function _compute_classes() {
		$classes = [];
		$namespace = '';
		$index = 0;
		while ($index < $this->tokens_length) {
			[$type, $text] = $this->token($index);
			$index = $index + 1;
			if ($type === T_NAMESPACE) {
				$namespace = $this->capture_next($index, [
					T_WHITESPACE,
					T_STRING,
					T_NS_SEPARATOR,
				]);
				$namespace = trim($namespace) . '\\';
			} elseif ($type === T_CLASS) {
				$class = $this->capture_next($index, [
					T_WHITESPACE,
					T_NS_SEPARATOR,
					T_STRING,
				], [
					T_IMPLEMENTS,
					T_EXTENDS,
					'{',
				]);
				$classes[] = $namespace . trim($class);
			}
		}
		return $classes;
	}

	/**
	 * Walk the token list and extract top-level function names
	 *
	 * @return string[]
	 */
	private function _compute_functions() {
		$functions = [];
		$namespace = null;
		$index = 0;
		while ($index < $this->tokens_length) {
			[$type, $text] = $this->token($index);
			$index = $index + 1;
			if ($type === T_NAMESPACE) {
				$namespace = $this->capture_next($index, [
					T_WHITESPACE,
					T_STRING,
					T_NS_SEPARATOR,
				]);
				$namespace = trim($namespace) . '\\';
			} elseif ($type === T_FUNCTION) {
				$function = $this->capture_next($index, [
					T_WHITESPACE,
					T_STRING,
				], [
					'(',
				], [
					'&',
				]);
				$functions[] = $namespace . trim($function);
			} elseif ($type === T_CLASS) {
				$index = $this->advance_to($index, '{');
				$index = $this->skip_brackets($index);
			}
		}
		return $functions;
	}

	/**
	 *
	 * @param index $index
	 * @param string|integer $token_type
	 */
	private function advance_to($index, $find_type) {
		do {
			[$type] = $this->token($index);
			if ($type === $find_type) {
				return $index;
			}
			++$index;
		} while ($index < $this->tokens_length);
		$this->app->logger->warning('{method} skipped beyond EOF for {file}', [
			'method' => __METHOD__,
			'file' => $this->file,
		]);
		return $index;
	}

	/**
	 * Skip past balanced {} brackets, assuming first token ($index) is
	 *
	 * @param unknown $index
	 * @return unknown
	 */
	private function skip_brackets(&$index) {
		$depth = 0;
		$lasttype = null;
		$result = '';
		do {
			[$type, $text] = $this->token($index);
			if ($type === '{') {
				++$depth;
			}
			if ($lasttype === '}') {
				--$depth;
			}
			$result .= $text;
			if ($depth === 0) {
				return $index;
			}
			++$index;
			$lasttype = $type;
		} while ($index < $this->tokens_length);
		$this->app->logger->warning('{method} skipped beyond EOF for {file}', [
			'method' => __METHOD__,
			'file' => $this->file,
		]);
		return $index;
	}

	/**
	 * Capture tokens starting at $index and capture only $capture_tokens
	 *
	 * @param unknown $index
	 * @param array $capture_tokens
	 * @param array $stop_tokens
	 * @return string|unknown|mixed
	 */
	private function capture_next(&$index, array $capture_tokens, array $stop_tokens = [], array $ignore_tokens = []) {
		$capture = '';
		while ($index < $this->tokens_length) {
			[$type, $text] = $this->token($index);
			if (!in_array($type, $ignore_tokens)) {
				if (in_array($type, $capture_tokens)) {
					$capture .= $text;
				} elseif (in_array($type, $stop_tokens)) {
					$index--;
					return $capture;
				} else {
					return $capture;
				}
			}
			++$index;
		}
		$this->app->logger->warning('{method} skipped beyond EOF for {file}', [
			'method' => __METHOD__,
			'file' => $this->file,
		]);
		return $capture;
	}

	/**
	 * Output all tokens
	 */
	public function dump_tokens(): void {
		$i = 0;
		while ($i < $this->tokens_length) {
			[$type, $text] = $this->token($i);
			echo token_name($type) . ': ' . StringTools::ellipsis_word($text) . "\n";
		}
	}
}
