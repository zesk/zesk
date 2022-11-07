<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use zesk\Test\Exception_Incomplete;
use zesk\Test\Exception_Skipped;
use zesk\Test\Exception as TestException;
use zesk\Test\Method;

/**
 *
 * @author kent
 * @deprecated 2022 Use PHPUnit
 */
class Test extends Hookable {
	/**
	 *
	 * @var string
	 */
	public const PHP_ERROR_MARIAH = 'PHP-ERROR';

	/**
	 * Statistics for test run
	 *
	 * @var array
	 */
	public array $stats = ['test' => 0, 'pass' => 0, 'fail' => 0, 'skip' => 0, 'assert' => 0, ];

	/**
	 *
	 * @var array
	 */
	protected array $load_modules = [];

	/**
	 * Method => settings
	 *
	 * @var array[string]
	 */
	private array $tests = [];

	/**
	 * Keys to tests/tests_status/test_results
	 *
	 * @var string[]
	 */
	private array $test_queue = [];

	/**
	 * Pass/fail status
	 *
	 * Method => true/false/null
	 *
	 * @var mixed[string]
	 */
	private array $test_status = [];

	/**
	 * Method return value storage.
	 * Failed tests do not return anything so their values will be empty.
	 *
	 * Method => mixed
	 *
	 * @var mixed[string]
	 */
	private array $test_results = [];

	/**
	 * Current test method
	 *
	 * @var Method
	 */
	private ?Method $test = null;

	/**
	 * Current test method arguments
	 *
	 * @var array
	 */
	private array $test_args = [];

	/**
	 * Last test result
	 *
	 * @var boolean
	 */
	private bool $test_result = true;

	/**
	 * Previous test output
	 *
	 * @var string
	 */
	protected string $last_test_output = '';

	/**
	 * Cache directory for this test
	 *
	 * @var string
	 */
	private string $cache_dir = '';

	/**
	 * Constructs a new Test_Unit object
	 *
	 * @param string $options
	 * @throws Exception_Lock
	 */
	public function __construct(Application $application, array $options = []) {
		self::init();
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->call_hook('construct');
		if ($this->load_modules) {
			$this->log('Loading modules: {load_modules}', ['load_modules' => $this->load_modules, ]);
			$this->application->modules->load($this->load_modules);
			$this->application->configured(true);
			$this->application->deprecated();
		}
	}

	/**
	 * Make sure we're initialized with basic error reporting
	 */
	public static function init(): void {
		static $inited = false;
		if (!$inited) {
			//echo "Error reporting enabled...";
			$inited = true;
			error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
			ini_set('display_errors', '1');
			$line = str_repeat('=', 80) . "\n";
			ini_set('error_prepend_string', $line . self::PHP_ERROR_MARIAH . ":\n");
			ini_set('error_append_string', "\n" . $line);
		}
	}

	/**
	 * Parse DocComment for a test
	 *
	 * @param string $comment
	 * @return array
	 */
	private function parse_doccomment(string $comment): array {
		return DocComment::instance($comment, [DocComment::OPTION_LIST_KEYS => ['test_module', ], ])->variables();
	}

	/**
	 * Begin a test
	 *
	 * @param string $test
	 * @param array $settings
	 * @param array $arguments
	 * @throws Exception_Semantics
	 */
	private function begin_test(Method $test, array $arguments = []): void {
		if ($this->test !== null) {
			throw new Exception_Semantics('{method}({name}): Already started test {this_name}', $test->variables() + ArrayTools::prefixKeys($this->test->variables(), 'this_') + ['method' => __METHOD__, ]);
		}
		$this->stats['test']++;
		$this->test = $test;
		$this->test_args = $arguments;
		$this->test_result = true;
		$no_buffer = $test->option('no_buffer', $this->option('no_buffer'));
		if (!$no_buffer) {
			ob_start();
		}
	}

	/**
	 * Finish a test
	 *
	 * @param array $settings
	 * @param string $error
	 * @throws string
	 */
	private function end_test(\Exception $error = null): void {
		$test = $this->test;
		if ($test === null) {
			if ($error !== null) {
				throw $error;
			}
			return;
		}

		$name = $test->name();
		$expected_exception = $test->option('expectedException', $test->option('expected_exception'));
		$error_class = is_object($error) ? $error::class : gettype($error);
		if ($expected_exception) {
			if ($expected_exception === $error_class) {
				$this->stats['pass']++;
				$this->test_results[$name] = null;
				$this->test_status[$name] = avalue($this->test_status, $name, true);
			} else {
				$this->stats['fail']++;
				$this->test_status[$name] = false;
				$this->log("Expected exception $expected_exception and received $error_class");
			}
		} else {
			if ($error instanceof Exception_Incomplete) {
				$this->report($error, 'INCOMPLETE');
			} elseif ($error instanceof Exception_Skipped) {
				$this->report($error, 'SKIPPED');
			} elseif ($error !== null) {
				$this->test_result = false;
				$this->report($error);
			}
			if ($this->test_result === false) {
				$this->stats['fail']++;
				$this->test_status[$name] = false;
			} elseif ($this->test_result === true) {
				$this->stats['pass']++;
				$this->test_status[$name] = avalue($this->test_status, $name, true);
			} elseif ($this->test_result === null) {
				$this->stats['skip']++;
				$this->test_status[$name] = null;
			} else {
				$this->application->logger->debug(PHP::dump($this->test_result));
				$this->test_status[$name] = null;
			}
		}

		$this->test = null;
		$this->test_args = [];
		$no_buffer = $test->option('no_buffer', $this->option('no_buffer'));
		if (!$no_buffer) {
			$this->last_test_output = ob_get_clean();
		} else {
			$this->last_test_output = '';
		}
	}

	/**
	 * Internal override method to set up a suite of tests
	 */
	protected function initialize(): void {
	}

	/**
	 * Internal override method to cleanup after suite of tests is completed
	 */
	protected function cleanup(): void {
	}

	/**
	 * Log a message
	 *
	 * @param string|array $message
	 * @param array $arguments
	 *            Arguments in the message
	 * @return self
	 */
	public function log(string|array $message, array $arguments = []): self {
		if (is_array($message)) {
			$message = Text::format_pairs($message);
		}
		if (empty($message)) {
			$message = '*empty message from {calling_function}*';
			$arguments['calling_function'] = calling_function(0);
		}
		if ($this->optionBool('debug_logger_config')) {
			if (method_exists($this->application->logger, 'dump_config')) {
				echo $this->application->logger->dump_config();
			}
		}
		$this->application->logger->log($arguments['severity'] ?? 'info', $message, $arguments);
		return $this;
	}

	/**
	 * Log an error
	 *
	 * @param string $message
	 * @param array $arguments
	 * @return self
	 */
	protected function error(string $message, array $arguments = []): self {
		return $this->log($message, ['severity' => 'error', ] + $arguments);
	}

	/**
	 * Check if a test should actually run, given its doccomment settings
	 *
	 * @param string $name
	 *            Test to run
	 * @param array $settings
	 *            Doccomment settings for a test
	 * @return boolean Whether to run or not
	 */
	private function _test_should_run(string $name, array $settings): bool {
		foreach (to_list('test;zesk_test') as $k) {
			if (array_key_exists($k, $settings)) {
				return true;
			}
		}
		if (!begins($name, 'test_')) {
			return false;
		}
		foreach (to_list('not_test;skip;notest;no_test;nottest') as $k) {
			if (array_key_exists($k, $settings)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Given a class, determine the methods which are eligible test methods
	 *
	 * @param string $class
	 * @return array of test => settings
	 */
	private function _determine_run_methods(string $class): array {
		try {
			$reflection = new \ReflectionClass($class);
			$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
			$tests = [];
			foreach ($methods as $method) {
				/* @var $method \ReflectionMethod */
				if ($method->getDeclaringClass()->getName() !== $class) {
					continue;
				}
				$method_name = $method->getName();
				$docComment = $method->getDocComment();
				$settings = is_string($docComment) ? $this->parse_doccomment($docComment) : [];
				if ($this->optionBool('debug_test_settings')) {
					if (count($settings) > 0) {
						echo "$method_name:\n";
						echo Text::format_pairs($settings, '    ');
					} else {
						echo "$method_name: no settings\n";
					}
				}
				if ($this->_test_should_run($method_name, $settings)) {
					$tests[$method_name] = new Method($this, $method_name, $settings);
				}
			}
			return $tests;
		} catch (\ReflectionException $e) {
			$this->application->logger->error("Unable to reflect on $class");
			$this->application->logger->error($e->getMessage());
			return [];
		}
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function has_test(string $name): bool {
		return array_key_exists($name, $this->tests);
	}

	/**
	 *
	 *
	 * @param string $name
	 * @return bool|null
	 */
	final public function get_test_result(string $name): ?bool {
		return $this->test_results[$name] ?? null;
	}

	/**
	 *
	 * @param string $name
	 */
	final public function has_test_result(string $name): bool {
		return array_key_exists($name, $this->test_results);
	}

	/**
	 * Getter/setter for last test output
	 *
	 * @param string $name
	 */
	final public function last_test_output(string $set = null) {
		if ($set !== null) {
			$this->application->deprecated('setter');
		}
		return $this->last_test_output;
	}

	/**
	 * Setter for last test output
	 *
	 * @param string $set
	 * @return self
	 */
	final public function setLastTestOutput(string $set) {
		$this->last_test_output = $set;
		return $this;
	}

	/**
	 *
	 * @param string $name
	 * @return ?string
	 */
	final public function find_test(string $name) {
		return $this->tests[$name] ?? null;
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function can_run_test($name) {
		if (!$this->has_test($name)) {
			return false;
		}
		/* @var $test Method */
		$test = $this->find_test($name);
		if (!$test) {
			return false;
		}
		if ($test->has_dependencies()) {
			return $test->dependencies_have_been_met();
		}
		return true;
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function is_test_queued($name) {
		return in_array($name, $this->test_queue);
	}

	/**
	 * Should we put off this test until later (dependencies?)
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function should_defer_test($name) {
		/* @var $test Method */
		$test = $this->find_test($name);
		if (!$test) {
			return false;
		}
		if ($test->has_dependencies()) {
			if ($test->dependencies_can_be_met()) {
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * @param Method $method
	 */
	final public function _run_test_method(Method $method, array $arguments): bool {
		$name = $method->name();

		try {
			$this->begin_test($method, $arguments);
			$this->test_results[$name] = call_user_func_array([$this, $name, ], $arguments);
			$this->end_test();
		} catch (\Exception $e) {
			$this->end_test($e);
		}
		return $this->test_result;
	}

	/**
	 * Main loop
	 */
	final public function run() {
		if ($this->optionBool('disabled')) {
			$this->log('{class} is disabled', ['class' => get_class($this), ]);
			$this->stats['skip']++;
			return true;
		}
		$class = get_class($this);
		$tests = $this->_determine_run_methods($class);

		try {
			$this->initialize();
		} catch (Exception_Incomplete $e) {
			$this->stats['skip']++;
			return true;
		} catch (Exception_Skipped $e) {
			$this->stats['skip']++;
			return true;
		}
		$this->tests = $tests;
		$this->test_queue = array_keys($tests);
		$this->test_status = [];
		$this->test_results = [];

		$locale = $this->application->locale;
		$deferred = [];
		while (count($this->test_queue) > 0) {
			$name = array_shift($this->test_queue);
			$test = $this->tests[$name];

			if ($this->can_run_test($name)) {
				if ($this->optionBool('debug_test_method')) {
					$this->log($locale->__('# Running {class}::{name}', [
						'class' => get_class($this),
						'name' => $name,
					]));
				}
				$test->run();
				$failed = ($this->test_status[$name] ?? null) !== true;
				if (!$failed) {
					if (($offset = strpos($this->last_test_output, self::PHP_ERROR_MARIAH)) !== false) {
						$this->log('Test output contained {mariah} at offset {n}', [
							'mariah' => self::PHP_ERROR_MARIAH,
							'n' => $offset,
						]);
						$this->test_status[$name] = false;
						$failed = true;
					}
				}
				$this->log($locale->__('# {class_test}: {status}', [
					'class_test' => Text::lalign("$class::$name", 80),
					'status' => $failed ? 'FAIL' : 'OK',
				]));
				if (($failed || $this->optionBool('verbose')) && !empty($this->last_test_output)) {
					$this->application->logger->info("Last test output:\n{output}--- End of output", ['output' => "\n" . Text::indent($this->last_test_output, 1, true), ]);
				}
			} elseif ($this->should_defer_test($name)) {
				if (isset($deferred[$name])) {
					$this->application->logger->info('Test deferred already, skipping {name}', ['name' => $name, ]);
					$this->test_status[$name] = 'skipped';
					$this->stats['skip']++;
				} else {
					$deferred[$name] = true;
					$this->test_queue[] = $name;
				}
			} else {
				$this->test_status[$name] = 'skipped';
				$this->stats['skip']++;
			}
		}

		$this->cleanup();
		return $this->stats['fail'] === 0;
	}

	/**
	 *
	 * @param \Exception $e
	 * @param string $result
	 */
	final public function report(\Exception $e, $result = 'FAILED'): void {
		$this->log(' - Exception: ' . $e::class . "\n");
		$this->log(" -    Result: $result\n");
		$code = $e->getCode();
		if ($code !== 0) {
			$this->log(' -      Code: ' . $e->getCode() . "\n");
		}
		$this->log(' -   Message: ' . $e->getMessage() . "\n");
		$this->log_backtrace($e->getTrace());
	}

	final public function log_backtrace(array $stackframes): void {
		$their_stack = [];
		foreach ($stackframes as $frame) {
			$file = $line = $function = $class = $type = $args = null;
			extract($frame, EXTR_IF_EXISTS);
			$descriptor = "$file:$line";
			if (empty($file)) {
				$descriptor = 'main';
			}
			$method = "$class$type$function";
			$left = $method;
			$right = $descriptor;
			$line = Text::lalign($left, 50) . " -- $right";
			if ($file !== __FILE__) {
				$their_stack[] = $line;
			}
			if (empty($file)) {
				break;
			}
		}
		echo implode("\n", $their_stack) . "\n";
	}

	/**
	 *
	 * @param string $message
	 *            Why this test failed.
	 * @param array $arguments
	 *            Arguments for the test failure message
	 *
	 * @throws TestException
	 */
	final public function fail(string $message, array $arguments = []): void {
		$this->test_result = false;
		if ($this->optionBool('debugger')) {
			debugger_start_debug();
		}

		throw new TestException($message, $arguments);
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 * @throws Exception_Incomplete
	 */
	final public function markTestIncomplete(string $message): void {
		$this->test_result = false;

		throw new Exception_Incomplete($message);
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 * @throws Exception_Skipped
	 */
	final public function markTestSkipped(string $message): void {
		$this->test_result = true;

		throw new Exception_Skipped($message);
	}

	/**
	 *
	 * @param boolean|string $condition
	 *            Boolean or string to evaluate condition
	 * @param string $message
	 *            What the assertion is about
	 * @param boolean $should_fail
	 *            This assertion should actually fail (test for false)
	 * @throws TestException
	 */
	final public function assert(bool|string $condition, string $message = null, bool $should_fail = false): void {
		$this->stats['assert']++;
		if (is_string($condition)) {
			$result = eval("return $condition;");
		} else {
			$result = $condition;
		}
		if ($should_fail) {
			if ($result) {
				$this->fail('Assertion should have failed but didn\'t: ' . PHP::dump($condition) . " ($message)");
			}
		} elseif (!$result) {
			$this->fail("Test failed $condition ($message)");
		}
	}

	/**
	 *
	 * @param string $module
	 * @return Module
	 */
	final public function load_module($module) {
		$app_module = $this->application->module;
		$app_module->load($module);
		$this->assert_true($app_module->loaded($module), "Module $module is not found");
		return $app_module->object($module);
	}

	/**
	 *
	 * @param unknown $modules
	 */
	final public function assert_modules(array $modules): void {
		$app_module = $this->application->module;
		$modules = to_list($modules);
		foreach ($modules as $module) {
			$this->assert_true($app_module->loaded($module), "Module $module is not found");
		}
	}

	/**
	 * Assert a value is false
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_false($condition, $message = null) {
		return $this->assert($condition, $message, true);
	}

	/**
	 * Assert a value is true
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_true($condition, $message = null): void {
		$this->assert($condition, $message, false);
	}

	/**
	 * Assert a value is a string
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_string($mixed, $message = null): void {
		$this->assert(is_string($mixed), '!is_string(' . type($mixed) . " $mixed) $message", false);
	}

	/**
	 * Assert a value is numeric
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_numeric($mixed, $message = null): void {
		$this->assert(is_numeric($mixed), '!is_numeric(' . type($mixed) . " $mixed) $message", false);
	}

	/**
	 * Assert a value is an integer
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_integer($mixed, $message = null): void {
		$this->assert(is_int($mixed), '!is_integer(' . type($mixed) . " $mixed) $message", false);
	}

	/**
	 * Assert a value is an array
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_array($mixed, $message = null): void {
		$this->assert(is_array($mixed), '!is_array(' . type($mixed) . ") $message", false);
	}

	/**
	 * Assert a value is an instanceof a class
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_instanceof($mixed, $instanceof, $message = null): void {
		$this->assert($mixed instanceof $instanceof, '!' . type($mixed) . " instanceof $instanceof $message", false);
	}

	final public function assert_class_exists($class, $message = null): void {
		$this->assert_is_string($class, 'Class passed to ' . __METHOD__ . ' should be string');
		$default_message = "Asserted class $class exists when it does not";

		try {
			$this->assert(class_exists($class), $message ? $message : $default_message);
		} catch (Exception_Class_NotFound $e) {
			$this->assert(false, $message ? $message : $default_message);
		}
	}

	/**
	 * Assert a value is an instanceof a class
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_implements($mixed, $instanceof, $message = null): void {
		$interfaces = class_implements($mixed);
		$this->assert(in_array($instanceof, $interfaces), '!' . type($mixed) . " implements $instanceof (does implement " . implode(', ', $interfaces) . ") $message", false);
	}

	/**
	 * Assert a value is a positive number
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_positive($value, $message = null): void {
		$this->assert($value > 0, "$value > 0 : $message", false);
	}

	/**
	 * Assert a value is not NULL
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_not_null($value, $message = null): void {
		if (!$message) {
			$message = flatten($value);
		}
		$this->assert($value !== null, "Asserted not NULL failed: \"$message\" (" . type($value) . ')', false);
	}

	/**
	 * Assert a value is a negative number
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_negative($value, $message = null): void {
		$this->assert($value < 0, "$value < 0 : $message", false);
	}

	/**
	 * Assert a value is null
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_null($value, $message = null): void {
		$this->assert($value === null, "$value === null : $message", false);
	}

	/**
	 * Assert two arrays are equal
	 *
	 * @param array $actual
	 * @param array $expected
	 * @param string $message
	 * @param boolean $strict
	 */
	final protected function assert_arrays_equal($expected, $actual, $message = null, $strict = true): void {
		$this->assert(is_array($actual), gettype($actual) . ' is not an array');
		$this->assert(is_array($expected), gettype($expected) . ' is not an array');
		$this->assert_equal($expected, $actual, $message, $strict);
	}

	final protected function assert_array_key_exists(array $array, $key, $message = null): void {
		if ($message === null) {
			$message = "Array does not contain key: $key (keys: " . implode(', ', array_keys($array)) . ')';
		}
		$this->assert(array_key_exists($key, $array), $message);
	}

	final protected function assert_array_key_not_exists(array $array, $key, $message = null): void {
		if ($message === null) {
			$message = "Array does contain key but should not: $key (keys: " . implode(', ', array_keys($array)) . ')';
		}
		$this->assert(!array_key_exists($key, $array), $message);
	}

	final protected function assert_in_array(array $array, $mixed, $message = null): void {
		if ($message === null) {
			$message = "Array does not contain value: $mixed (values: " . implode(', ', array_values($array)) . ')';
		}
		$this->assert(in_array($mixed, $array), $message);
	}

	final protected function assert_not_in_array(array $array, $mixed, $message = null): void {
		if ($message === null) {
			$message = "Array should not contain value: $mixed (values: " . implode(', ', array_values($array)) . ')';
		}
		$this->assert(!in_array($mixed, $array), $message);
	}

	final protected function assert_contains($haystack, $needle, $message = null): void {
		if ($message === null) {
			$message = "$haystack\n=== DOES NOT CONTAIN STRING===\n$needle";
		}
		$this->assert(str_contains($haystack, $needle), $message);
	}

	final protected function assert_string_begins($haystack, $needle, $message = null): void {
		if ($message === null) {
			$message = "$haystack\n=== DOES NOT BEGIN WITH STRING===\n$needle";
		}
		$this->assert(str_starts_with($haystack, $needle), $message);
	}

	final protected function assert_equal($expected, $actual, $message = null, $strict = true): void {
		$this->stats['assert']++;
		$message .= "\nassert_equal failed:\n";
		$message .= '- Actual: ' . gettype($actual) . ': ' . $this->dump($actual) . "\n";
		$message .= 'Expected: ' . gettype($expected) . ': ' . $this->dump($expected);
		if (is_scalar($actual) && is_scalar($expected)) {
			if (is_float($actual) && is_float($expected)) {
				if (abs($actual - $expected) > 0.00001) {
					$this->fail($message);
				}
			} elseif ($strict) {
				$this->assert($actual === $expected, $message);
			} else {
				$this->assert($actual == $expected, $message);
			}
		} elseif (is_array($actual) && is_array($expected)) {
			$this->assert_equal_array($expected, $actual, $message, $strict);
		} elseif (is_object($actual) && is_object($expected)) {
			$this->assert_equal_object($expected, $actual, $message, $strict);
		} elseif (is_null($actual) && is_null($expected)) {
			return;
		} else {
			$this->fail("Unhandled or mismatched types: $message");
		}
	}

	final protected function assert_not_equal($expected, $actual, $message = null, $strict = true): void {
		if ($message === null) {
			$message = gettype($actual) . ': ' . $this->dump($actual) . ' === ' . gettype($expected) . ': ' . $this->dump($expected);
		}
		if ($strict) {
			$this->assert($actual !== $expected, $message);
		} else {
			$this->assert($actual != $expected, $message);
		}
	}

	final public function assert_equal_object($expected, $actual, $message = ''): void {
		$this->assert($actual::class === $expected::class, $message . 'get_class(' . $actual::class . ') === get_class(' . $expected::class . ')');
		$this->assert($actual == $expected, $message . "\n" . $this->dump($actual) . ' !== ' . $this->dump($expected));
	}

	/**
	 * Central place to dump variables to output.
	 * Use PHP output to facilitate generating tests whose output can be copied for first writing
	 * and manual verification.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function dump($value) {
		return PHP::singleton()->settings_one()->render($value);
	}

	final protected function assert_equal_array($expected, $actual, $message = '', $strict = true, $order_matters = false): void {
		$this->stats['assert']++;
		if (!is_array($actual)) {
			$this->fail("$message: \$actual is not an array: " . $this->dump($actual, false));
		}
		if (!is_array($expected)) {
			$this->fail("$message: \$expected is not an array: " . $this->dump($expected, false));
		}
		if (count($actual) !== count($expected)) {
			$actual_keys = implode(', ', array_keys($actual));
			$expected_keys = implode(', ', array_keys($expected));
			$this->fail("$message: Arrays are diferent sizes: ACTUAL " . count($actual) . " ($actual_keys) !== EXPECTED " . count($expected) . " ($expected_keys)");
		}
		foreach ($actual as $k => $v) {
			if (!array_key_exists($k, $expected)) {
				$this->fail("$message: $k doesn't exist in 2nd array");
			}
			if ($strict && gettype($v) !== gettype($expected[$k])) {
				$this->fail("$message: types do not match for key $k: (" . gettype($v) . ') !== (' . gettype($expected[$k]) . ')');
			}
			if (is_array($v)) {
				$this->assert_equal($v, $expected[$k], "[$k] $message", $strict);
			} elseif (is_object($v)) {
				$this->assert($v::class === get_class($expected[$k]), 'Classes don\'t match ' . $v::class . ' === ' . get_class($expected[$k]) . ": $message");
				$this->assert_equal($v, $expected[$k], "Comparing Key($k) => ");
			} elseif ($strict) {
				if ($v !== $expected[$k]) {
					$this->fail("$message: $k doesn't match: $v !== " . $expected[$k]);
				}
			} else {
				if ($v != $expected[$k]) {
					$this->fail("$message: $k doesn't match: $v !== " . $expected[$k]);
				}
			}
		}
		if ($order_matters) {
			$this->assert_equal(implode(';', array_keys($actual)), implode(';', array_keys($expected)), 'Ordering is different: ' . implode(';', array_keys($actual)) === implode(';', array_keys($expected)));
		}
	}

	final protected function assert_array_contains($subset, $superset, $message = ''): void {
		if (!is_array($subset)) {
			$this->fail("$message: \$subset is not an array: " . $this->dump($subset, false));
		}
		if (!is_array($superset)) {
			$this->fail("$message: \$superset is not an array: " . $this->dump($superset, false));
		}
		foreach ($subset as $k => $v) {
			$this->assert(array_key_exists($k, $superset), "$message: Key exists in superset $k (subset value=$v)");
			if (is_array($v)) {
				$this->assert_arrays_equal($v, $superset[$k], "$message: Key $k in array");
			} else {
				$this->assert($superset[$k] === $v, "key $k: $superset[$k] !== $v");
			}
		}
	}

	/**
	 * Create a sandbox folder to test with
	 *
	 * @param unknown $file
	 * @param unknown $auto_delete
	 * @see self::sandbox
	 */
	final protected function test_sandbox($file = null, $auto_delete = true) {
		return $this->sandbox($file, $auto_delete);
	}

	/**
	 */
	final public function sandbox($file = null, $auto_delete = true) {
		$cache_dir = $this->application->path('cache/test/' . $this->application->process->id());
		if (!is_dir($cache_dir)) {
			if (!mkdir($cache_dir, 0o777, true)) {
				$this->fail("test_sandbox: Can't create $cache_dir");
			}
			$this->cache_dir = $cache_dir;
			chmod($cache_dir, 0o770);
			if ($auto_delete) {
				$this->application->hooks->add('exit', [$this, '_test_sandbox_shutdown', ]);
			}
		}
		return path($cache_dir, $file);
	}

	/**
	 * Delete cache dir after test runs
	 */
	final public function _test_sandbox_shutdown(): void {
		$cache_dir = $this->cache_dir;
		$this->cache_dir = '';
		if ($cache_dir === '') {
			echo 'No cache directory to delete';
			return;
		}
		echo "Deleting $cache_dir ...\n";
		if (is_dir($cache_dir)) {
			Directory::delete($cache_dir);
		}
	}

	/**
	 * @not_test
	 *
	 * @param unknown_type $name
	 * @param unknown_type $extra_cols
	 * @param unknown_type $uniq
	 */
	final public function test_table($name, $extra_cols = null, $uniq = true): void {
		$cols[] = 'id int(11) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT';
		$cols[] = 'foo int(11) NOT NULL';
		if (is_string($extra_cols)) {
			$cols[] = $extra_cols;
		} elseif (is_array($extra_cols)) {
			$cols = array_merge($cols, $extra_cols);
		}
		if ($uniq) {
			$cols[] = 'UNIQUE `f` (`foo`)';
		}
		$cols = implode(', ', $cols);
		$create_sql = "CREATE TABLE `$name` ( $cols )";
		$this->test_table_sql($name, $create_sql);
	}

	/**
	 * @not_test
	 *
	 * @param unknown_type $name
	 * @param unknown_type $extra_cols
	 * @param unknown_type $uniq
	 */
	final protected function test_table_object(ORM $object): void {
		$this->test_table_sql($object->table(), $object->schema());
		$object->schema_changed();
	}

	/**
	 * @not_test
	 *
	 * @param unknown_type $name
	 * @param unknown_type $extra_cols
	 * @param unknown_type $uniq
	 */
	final public function test_table_sql($name, $create_sql): void {
		$db = $this->application->database_registry();
		$db->query("DROP TABLE IF EXISTS `$name`");
		$db->query($create_sql);
		if (!$this->optionBool('debug_keep_tables')) {
			register_shutdown_function([$db, 'query', ], "DROP TABLE IF EXISTS `$name`");
		}
	}

	/**
	 *
	 * @param string $table
	 * @param array $match
	 * @param string $dbname
	 */
	final protected function test_table_match($table, array $match = [], $dbname = ''): void {
		$db = $this->application->database_registry();
		$headers = null;
		$header_row = null;
		$dbrows = [];
		foreach ($match as $row) {
			if (!$headers) {
				$headers = $row;
				$header_row = $row;
			} else {
				$mapped_row = [];
				foreach ($headers as $k => $label) {
					if ($label[0] === '-') {
						continue;
					}
					$mapped_row[$label] = $row[$k];
				}
				$dbrows[] = $mapped_row;
			}
		}
		$headers = [];
		foreach ($header_row as $header) {
			if ($header[0] === '-') {
				continue;
			}
			$headers[] = $header;
		}
		$rows = $db->queryArray('SELECT ' . implode(',', $headers) . " FROM $table");
		$this->assert_arrays_equal($rows, $dbrows, "Matching $table to row values", false);
	}

	/**
	 * Run a unit test usually externally.
	 * This is called using
	 * zesk-command.php application.inc path/to/some_test.inc eval
	 * 'Test_Unit::run_class("Some_Test")'
	 *
	 * @param string $class
	 *            The class to run
	 * @param null $object
	 *            Optional return value to get the $object created back
	 * @return boolean Whether the test passed
	 * @throws Exception_Invalid|Exception_Lock|Exception_Invalid
	 */
	public static function run_one_class(Application $application, $class, array $options, &$object = null) {
		try {
			$object = $application->objects->factory($class, $application);
		} catch (\Exception $e) {
			throw new Exception_Invalid('Unable to create {class} - Exception {exception_class}: {message}', [
				'class' => $class,
			] + Exception::exception_variables($e));
		}
		/* @var $object Test_Unit */
		if (!$object instanceof Interface_Testable) {
			throw new Exception_Invalid('{class} is not an instance of {testable_class}', [
				'class' => $class,
				'testable_class' => Interface_Testable::class,
			]);
		}
		$object->setOptions($options);
		$object->inheritConfiguration();
		return $object->run();
	}

	/**
	 * Run a unit test usually externally.
	 * This is called using
	 * zesk-command.php application.inc path/to/some_test.inc eval
	 * 'Test_Unit::run_class("Some_Test")'
	 *
	 * @param string $class
	 *            The class to run
	 * @param null $object
	 *            Optional return value to get the $object created back
	 * @return exits, never returns
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Semantics
	 */
	public static function run_class(Application $application, $class, &$object = null) {
		if (empty($class)) {
			throw new Exception_Semantics('{method}: No class specified', ['method' => __METHOD__, ]);
		}
		$settings = self::_configuration_load($application);
		if (!class_exists($class, false) && !$application->autoloader->load($class, true) && !self::_find_test($application, $class)) {
			throw new Exception_Class_NotFound($class);
		}
		exit(self::run_one_class($application, $class, $settings, $object) ? 0 : 1);
	}

	/**
	 *
	 * @param string $class
	 * @return boolean
	 */
	private static function _find_test(Application $application, string $class): bool {
		$low_class = StringTools::unsuffix(strtolower($class), '_test');
		$parts = explode('_', $low_class);
		array_pop($parts);
		$path = path(implode('/', $parts), 'test', $low_class . '_test.inc');
		$include = File::find_first(array_keys($application->autoloader->path()), $path);
		if (!$include) {
			return false;
		}
		include $include;
		return class_exists($class, false);
	}

	/**
	 * Given a class and a method, make the available method not-private to do blackbox testing
	 *
	 * @param string $class
	 * @param string|list $methods
	 * @return Closure[]
	 */
	public function expose_method(string $class, array $methods): array {
		$refl = new \ReflectionClass($class);
		$results = [];
		foreach ($methods as $method) {
			$cmethod = $refl->getMethod($method);
			$cmethod->setAccessible(true);
			$results[$method] = function ($object) use ($cmethod) {
				$args = func_get_args();
				array_shift($args);
				return $cmethod->invokeArgs($object, $args);
			};
		}
		return $results;
	}

	/**
	 * Synchronize the given classes with the database schema
	 *
	 * @param list|string $classes
	 * @param array $options
	 * @return array[classname]
	 */
	public function schema_synchronize(array|string $classes, array $options = []): array {
		$app = $this->application;
		$results = [];
		foreach (to_list($classes) as $class) {
			$class_object = $app->class_ormRegistry($class);
			$db = $class_object->database();
			$results[$class] = $db->queries($app->orm_module()->schema_synchronize($db, [$class, ], $options + ['follow' => true, ]));
		}
		return $results;
	}

	/**
	 *
	 * @return array
	 */
	private static function _configuration_load(Application $application): array {
		$test_command = new Command_Test($application, []);
		$configuration = $application->configuration;
		$config = $configuration->path_get('zesk\\Test_Unit::config', $test_command->default_configuration_file('test'));
		if (!$config) {
			return [];
		}
		$application->logger->debug("Loading configuration file $config");
		$settings = [];
		$loader = new Configuration_Loader([$config, ], new Adapter_Settings_Array($settings));

		$loader->load();

		if (toBool($configuration->path_get('zesk\\Command_Test::debug_config'))) {
			echo "Loaded configuration file:\n";
			echo Text::format_pairs($settings);
		}
		return $settings;
	}
}
