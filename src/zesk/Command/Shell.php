<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use Exception;
use Throwable;
use zesk\Application;
use zesk\Exception\FilePermission;
use zesk\Exception\SemanticsException;
use zesk\Exception\ParameterException;
use zesk\PHP;
use zesk\StopIteration;
use zesk\StringTools;

use function str_contains;
use function str_starts_with;

/**
 * Run arbitrary PHP code in the application context. Use --interactive or -i to run in interactive mode.
 *
 * The following values are set, by default in your evaluation context:
 *
 *     $app, $application - The current application context
 *     $command, $_, $this - The current `zesk\Command_Eval` object
 *
 * You can assign variables and state is maintained between command line calls. Note that references to variables
 * will not work as variables are collected and `extract`ed on each command-line invocation.
 *
 * Passing multiple parameters to `eval` will maintain state between them. So:
 *
 *    zesk eval '$a=1;$b=1' '$a+$b'
 *
 * @category Management
 */
class Shell extends SimpleCommand {
	protected array $shortcuts = ['shell', 'eval', 'evaluate'];

	protected array $option_types = [
		'skip-configure' => 'boolean', 'json' => 'boolean', 'interactive' => 'boolean', 'debug-state' => 'boolean',
		'*' => 'string',
	];

	protected array $option_help = [
		'skip-configure' => 'Skip application configuration', 'json' => 'Output results as JSON instead of PHP',
		'interactive' => 'Run interactively', 'debug-state' => 'Debug interactive state management', '*' => 'string',
	];

	protected array $option_chars = [
		'i' => 'interactive', 's' => 'skip-configure',
	];

	/**
	 * Variables saved before eval is run
	 *
	 * @var array
	 */
	private array $before_vars = [];

	/**
	 * Variables preserved between eval lines
	 *
	 * @var array
	 */
	private array $saved_vars = [];

	/**
	 * Run our eval command
	 *
	 * @return int
	 * @throws FilePermission
	 * @throws ParameterException
	 * @throws SemanticsException
	 */
	public function run(): int {
		if (!$this->optionBool('skip-configure')) {
			$this->configure('shell');
		}
		$this->handle_base_options();
		$this->saved_vars = [];
		while ($this->hasArgument()) {
			$arg = $this->getArgument('eval');
			if ($arg === '--') {
				break;
			}
			$this->verboseLog("Evaluating: $arg\n");
			ob_start();
			$result = $this->_eval($arg);
			$this->output_result($result, ob_get_clean());
		}
		if ($this->optionBool('interactive')) {
			return $this->interactive();
		}
		return 0;
	}

	/**
	 * Optionally output the result of the last evaluated code
	 *
	 * @param mixed $__result
	 * @param string $content
	 * @return void
	 */
	public function output_result(mixed $__result, string $content = ''): void {
		if ($__result !== null) {
			echo '# return ' . PHP::dump($__result) . "\n";
		}
		if ($content !== '') {
			echo $content . "\n";
		}
	}

	/**
	 * Interactive evaluation of commands
	 *
	 * @return int
	 * @throws SemanticsException
	 */
	public function interactive(): int {
		$this->history_file_path = $this->application->paths->userHome('eval-history.log');
		$name = get_class($this->application);
		$last_exit_code = 0;
		while (true) {
			try {
				$command = $this->prompt($name . '>');
			} catch (StopIteration) {
				return 0;
			}
			if (feof(STDIN)) {
				echo "\nExit\n";
				return $last_exit_code;
			}
			ob_start();

			try {
				$__result = $this->_eval($command);
				$last_exit_code = 0;
			} catch (Throwable $ex) {
				$content = ob_get_clean();
				echo '# exception ' . $ex::class . "\n";
				echo '# message ' . $ex->getMessage() . "\n";
				echo "# stack trace\n" . $ex->getTraceAsString() . "\n";
				if ($content) {
					echo "# Content\n$content\n";
				}
				$this->application->invokeHooks(Application::HOOK_COMMAND, [$this->application, $ex]);
				$last_exit_code = 99;
				continue;
			}
			$content = ob_get_clean();
			$this->output_result($__result, $content);
		}
	}

	/**
	 * Before evaluate, save global context variables
	 */
	private function _before_evaluate(array $vars): array {
		$this->before_vars = $vars;
		return $this->saved_vars;
	}

	/**
	 * After evaluate, determine if any new variables are present
	 * @param array $vars
	 */
	private function _after_evaluate(array $vars): void {
		$diff = array_diff_assoc($vars, $this->before_vars);
		foreach ($diff as $k => $v) {
			if (str_starts_with($k, '__')) {
				unset($diff[$k]);
			}
		}
		$this->saved_vars = $diff + $this->saved_vars;
		$this->before_vars = $vars;

		if ($this->optionBool('debug-state')) {
			if (count($diff) > 0) {
				echo 'New variables defined in state: ' . implode(', ', array_keys($diff)) . "\n";
			} else {
				echo "No new variables defined.\n" . _dump(array_keys($vars));
			}
		}
	}

	private function _prefix_commmand(string $string): string {
		$string = trim($string, " \n\r\t;");
		$string = preg_replace('/^return\s/', '', $string);
		// If contains multiple commands, then no prefix
		if (str_contains($string, ';')) {
			return $string;
		}
		$prefix = 'return';
		if (StringTools::begins($string, [
			'echo ', 'print ',
		]) || str_contains($string, ';')) {
			$prefix = '';
		}
		return $prefix . ' ' . $string;
	}

	/**
	 * Evaluate a PHP string and execute it in the application context
	 *
	 * Note that the state of $this->saved_vars may be updated based on newly defined variables.
	 * Does NOT support $a &= $b, however.
	 *
	 * @param string $__string Arbitrary PHP code
	 * @return mixed
	 * @throws Exception
	 */
	private function _eval(string $__string): mixed {
		$__eval = $this->_prefix_commmand($__string);

		try {
			$command = $_ = $this;
			$application = $app = $this->application;
			extract($this->_before_evaluate(get_defined_vars()), EXTR_SKIP);
			$__eval = '?' . '><' . "?php\n$__eval;\n";
			if ($this->optionBool('debug-state')) {
				$this->verboseLog("RAW PHP EVAL: $__eval");
			}
			$__result = eval($__eval);
			$this->_after_evaluate(get_defined_vars());
			return $__result;
		} catch (Exception $e) {
			throw $e;
		}
	}
}
