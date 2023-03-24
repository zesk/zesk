<?php
declare(strict_types=1);
/**
 *
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use ReflectionClass;
use ReflectionException;
use Throwable;
use zesk\Exception\ClassNotFound;
use zesk\Exception\Semantics;

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once(__DIR__ . '/functions.php');

/**
 *
 * @todo self::reset is NOT production ready
 * @author kent
 *
 */
class Kernel {
	/**
	 *
	 * @var array
	 */
	private static array $configurationDefaults = [
		__CLASS__ => [
			'applicationClass' => Application::class,
		],
	];

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 * @var Application[]
	 */
	private array $applications = [];

	public static null|self $singleton = null;

	private function __construct(Application $initial) {
		Compatibility::check();
		$this->application = $initial;
		$this->applications[$initial::class] = $initial;
		register_shutdown_function($this->shutdown(...));
	}

	public function shutdown(): void {
		foreach ($this->applications as $application) {
			try {
				$application->shutdown();
			} catch (Throwable $t) {
				PHP::log($t);
			}
		}
	}

	/**
	 * Fetch the kernel singleton. Avoid this call whenever possible.
	 *
	 * @return static
	 * @throws Semantics
	 */
	public static function singleton(): self {
		if (!self::$singleton) {
			throw new Semantics('Need to create singleton with {class}::factory first', ['class' => __CLASS__, ]);
		}
		return self::$singleton;
	}

	/**
	 * @return Application
	 */
	public function application(): Application {
		return $this->application;
	}

	/**
	 * Find app by class
	 *
	 * @param string $class
	 * @return Application|null
	 */
	public function applicationByClass(string $class): null|Application {
		return $this->applications[$class] ?? null;
	}

	/**
	 * @param Application $app
	 * @return void
	 */
	public function add(Application $app): void {
		$this->applications[$app::class] = $app;
	}

	private static function _stackFrameNormalize(array $stackFrame): array {
		return $stackFrame + [
			'file' => '', 'class' => '', 'type' => '', 'function' => '', 'line' => '',
		];
	}

	/**
	 * Return information about the calling function. Increase depth to look back in the stack frame.
	 *
	 * Pass -1 to test and get the information about this function. Not super useful.
	 * Pass 0 to get the immediately calling function (e.g. where you are before you called this)
	 * Pass 1 to get what called you (most useful)
	 *
	 * @param int $depth Depth 0 means the immediately calling function
	 * @return string[]
	 */
	public static function caller(int $depth = 1): array {
		$bt = debug_backtrace();
		$last = [];
		/* Skip current frame by default - depth zero always means calling function */
		$depth++;
		if ($depth > 0) {
			while ($depth-- !== 0) {
				$last = array_shift($bt);
			}
		}
		$last = self::_stackFrameNormalize($last);
		$top = self::_stackFrameNormalize(array_shift($bt) ?? [
			'file' => "-no calling function $depth deep-",
			'line' => -1,
			'class' => '',
			'function' => '-no calling function $depth deep-',
			'type' => '',
		]);

		$top['callingLine'] = $top['line'];
		$top['callingFile'] = $top['file'];
		$top['line'] = $last['line'];
		$top['file'] = $last['file'];
		$top['lineSuffix'] = ($top['line'] >= 0) ? ':' . $top['line'] : '';
		$top['method'] = $top['class'] . $top['type'] . $top['function'];
		$top['methodLine'] = $top['class'] . $top['type'] . $top['function'] . $top['lineSuffix'];
		$top['fileMethod'] = $top['file'] . ' ' . $top['method'];
		$top['fileMethodLine'] = $top['file'] . ' ' . $top['method'] . $top['lineSuffix'];
		return $top;
	}

	/**
	 * Returns the name of the function or class/method which called the current code.
	 * Useful for debugging.
	 *
	 * Moved from Debug:: class to assist in profiling bootstrap functions (for example)
	 * which don't have the autoloader set yet.
	 *
	 * @param int $depth
	 * @param bool $include_line
	 * @return string
	 * @see debug_backtrace()
	 * @see Debug::calling_function
	 */
	public static function callingFunction(int $depth = 1, bool $include_line = true): string {
		$top = self::caller($depth + 1);
		return $top[$include_line ? 'fileMethodLine' : 'fileMethod'];
	}

	/**
	 * Return a backtrace of the stack
	 *
	 * @param int $n The number of frames to output. Pass a negative number to pass all frames.
	 */
	public static function backtrace(int $n = -1): string {
		$bt = debug_backtrace();
		array_shift($bt);
		if ($n <= 0) {
			$n = count($bt);
		}
		$result = [];
		foreach ($bt as $i) {
			$file = 'closure';
			$line = '-none-';
			$class = '-no-class-';
			$type = $function = $args = null;
			extract($i, EXTR_OVERWRITE | EXTR_IF_EXISTS);
			$line = "$file: $line $class$type$function";
			if (is_array($args)) {
				$arg_dump = [];
				foreach ($args as $index => $arg) {
					if (is_object($arg)) {
						$arg_dump[$index] = $arg::class;
					} elseif (is_scalar($arg)) {
						$arg_dump[$index] = PHP::dump($arg);
					} else {
						$arg_dump[$index] = Types::type($arg);
					}
				}
				if (count($arg_dump)) {
					$line .= '(' . implode(', ', $arg_dump) . ')';
				}
			}
			$result[] = $line;
			if (--$n <= 0) {
				break;
			}
		}
		return implode("\n", $result);
	}

	/**
	 * Create an application
	 *
	 * @param array $options
	 * @return Application
	 * @throws ClassNotFound
	 */
	public static function createApplication(array $options = []): Application {
		$options['application.php'] = self::caller()['file'];
		$baseApplicationClass = Application::class;
		$cacheItemPool = $options[Application::OPTION_CACHE_POOL] ?? new CacheItemPool_NULL();
		unset($options[Application::OPTION_CACHE_POOL]);

		$configuration = Configuration::factory(self::$configurationDefaults)->merge(Configuration::factory()->setPath($baseApplicationClass, $options));
		$applicationClass = $configuration->getFirstPath([
			[$baseApplicationClass, Application::OPTION_APPLICATION_CLASS],
			[__CLASS__, Application::OPTION_APPLICATION_CLASS], [__CLASS__, 'application_class', ],
		], Application::class);

		try {
			$reflectionClass = new ReflectionClass($applicationClass);
			$app = $reflectionClass->newInstanceArgs([$configuration, $cacheItemPool]);
		} catch (ReflectionException $e) {
			throw new ClassNotFound($applicationClass, $e->getMessage(), Exception::exceptionVariables($e));
		}
		assert($app instanceof Application);
		if (!self::$singleton) {
			self::$singleton = new self($app);
		} else {
			self::$singleton->add($app);
		}
		return $app;
	}

	/**
	 * Who owns the copyright on the Zesk Application Framework for PHP
	 *
	 * @return string
	 */
	public static function copyrightHolder(): string {
		return 'Market Acumen, Inc.';
	}

	/**
	 * @return Application
	 * @throws Semantics
	 */
	public static function wakeupApplication(): Application {
		return Kernel::singleton()->application();
	}
}
