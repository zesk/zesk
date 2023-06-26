<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Application;

use Closure;
use Throwable;
use zesk\Application;
use zesk\HookMethod;
use zesk\PHP;
use zesk\RuntimeException;

/**
 *
 * @author kent
 *
 */
class Hooks {
	/**
	 *
	 * @var string
	 */
	public const HOOK_DATABASE_CONFIGURE = 'database_configure';

	/**
	 *
	 * @var string
	 */
	public const HOOK_CONFIGURED = 'configured';

	/**
	 * Reset the entire zesk application context
	 *
	 * @var string
	 */
	public const HOOK_RESET = 'reset';

	/**
	 * Called when the process is going to exit
	 *
	 * @var string
	 */
	public const HOOK_EXIT = 'exit';

	/**
	 * Output a debug log when a class is called with ::hooks but does not implement it
	 *
	 * @var boolean
	 */
	public bool $debug = false;

	/**
	 *
	 * @var Application
	 */
	public Application $application;

	/**
	 * HookMethod
	 *
	 * @var array:array:HookMethod
	 */
	protected array $hooksQueue = [];

	/**
	 * Whether a hook is a filter or not as registered.
	 *
	 * @var array
	 */
	protected array $hooksQueueType = [];

	/**
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	private static array $fatalErrors = [
		E_USER_ERROR => 'Fatal Error', E_ERROR => 'Fatal Error', E_PARSE => 'Parse Error', E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning',
	];

	/**
	 * Shutdown function to log errors
	 */
	private function _applicationExitCheck(): void {
		$prefix = 'Application Exit Check: ';
		if ($err = error_get_last()) {
			if (isset(self::$fatalErrors[$err['type']])) {
				$msg = self::$fatalErrors[$err['type']] . ': ' . $err['message'] . ' in ';
				$msg .= $err['file'] . ' on line ' . $err['line'];
				error_log($prefix . $msg);
			}
		}
	}

	/**
	 * @param string $hookName
	 * @param callable|Closure $method
	 * @param bool $filter
	 * @return self
	 */
	public function registerHook(string $hookName, callable|Closure $method, bool $filter = false): self {
		$this->_enforceFilterType($hookName, $filter);
		$hookMethod = new HookMethod($hookName, [], null, $filter);
		$hookMethod->setClosure($method instanceof Closure ? $method : $method(...), Hooks::callableString($method));
		$this->hooksQueue[$hookName][] = $hookMethod;
		$this->hooksQueueType[$hookName] = $filter;
		return $this;
	}

	/**
	 * @param string $hookName
	 * @param callable|Closure $method
	 * @return $this
	 */
	public function registerFilter(string $hookName, callable|Closure $method): self {
		return $this->registerHook($hookName, $method, true);
	}

	/**
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return void
	 */
	private function _enforceFilterType(string $hookName, bool $isFilter): void {
		if (!array_key_exists($hookName, $this->hooksQueueType)) {
			return;
		}
		if (($isType = $this->hooksQueueType[$hookName]) !== $isFilter) {
			throw new RuntimeException('{hookName} accessed as a {accessedAs} but created as {createdAs}}', [
				'hookName' => $hookName, 'accessedAs' => $isFilter ? 'filter' : 'hook',
				'createdAs' => $isType ? 'filter' : 'hook',
			]);
		}
	}

	/**
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return array
	 */
	public function peekHooks(string $hookName, bool $isFilter = false): array {
		$this->_enforceFilterType($hookName, $isFilter);
		return $this->hooksQueue[$hookName] ?? [];
	}

	/**
	 * @param string $hookName
	 * @return array
	 */
	public function peekFilters(string $hookName): array {
		$this->_enforceFilterType($hookName, true);
		return $this->hooksQueue[$hookName] ?? [];
	}

	/**
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return array
	 */
	public function hooksDequeue(string $hookName, bool $isFilter = false): array {
		$hooks = $this->peekHooks($hookName, $isFilter);
		unset($this->hooksQueue[$hookName]);
		unset($this->hooksQueueType[$hookName]);
		return $hooks;
	}

	/**
	 * For a series of hooks which are run once, for example.
	 *
	 * @param string $hookName
	 * @return array
	 */
	public function filtersDequeue(string $hookName): array {
		return $this->hooksDequeue($hookName, true);
	}

	/**
	 * Convert a callable to a string for output/debugging. Blank for anything which
	 * is not a unique ID.
	 *
	 * @param mixed $callable
	 * @return string
	 */
	public static function callableString(mixed $callable): string {
		if (is_array($callable)) {
			return is_object($callable[0]) ? strtolower(get_class($callable[0])) . '::' . $callable[1] : implode('::', $callable);
		} elseif (is_string($callable)) {
			return $callable;
		} elseif ($callable instanceof Closure) {
			return '';
		}
		return '';
	}

	/**
	 * Utility function to convert an array of callable strings into an array of strings
	 *
	 * @param Callable[] $callables
	 * @return string[]
	 */
	public static function callableStrings(array $callables): array {
		$result = [];
		foreach ($callables as $callable) {
			$result[] = self::callableString($callable);
		}
		return $result;
	}

	public function shutdown(): void {
		try {
			$this->application->invokeHooks(Hooks::HOOK_EXIT, [$this->application]);
		} catch (Throwable $e) {
			PHP::log($e);
		}
		$this->_applicationExitCheck();
		$this->hooksQueue = [];
		$this->hooksQueueType = [];
	}
}
