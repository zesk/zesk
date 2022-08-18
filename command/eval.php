<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

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
class Command_Eval extends Command_Base {
	protected array $option_types = [
		'skip-configure' => 'boolean',
		'json' => 'boolean',
		'interactive' => 'boolean',
		'debug-state' => 'boolean',
		'*' => 'string',
	];

	protected array $option_help = [
		'skip-configure' => 'Skip application configuration',
		'json' => 'Output results as JSON instead of PHP',
		'interactive' => 'Run interactively',
		'debug-state' => 'Run interactively. When running interactively the ',
		'*' => 'string',
	];

	protected array $option_chars = [
		'i' => 'interactive',
		's' => 'skip-configure',
	];

	/**
	 * Variables saved before eval is run
	 *
	 * @var array
	 */
	private $before_vars = null;

	/**
	 * Variables preserved between eval lines
	 *
	 * @var array
	 */
	private $saved_vars = null;

	/**
	 * Run our eval command
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run() {
		if (!$this->optionBool('skip-configure')) {
			$this->configure('eval');
		}
		$this->handle_base_options();
		$this->saved_vars = [];
		while ($this->has_arg()) {
			$arg = $this->get_arg('eval');
			if ($arg === '--') {
				break;
			}
			$this->verbose_log("Evaluating: $arg\n");
			ob_start();
			$result = $this->_eval($arg);
			$this->output_result($result, ob_get_clean());
		}
		if ($this->optionBool('interactive')) {
			return $this->interactive();
		}
	}

	/**
	 * Optionally output the result of the last evaluated code
	 *
	 * @param mixed $result
	 */
	public function output_result($__result, $content = ''): void {
		if ($__result === null) {
			if ($content !== '') {
				echo $content . "\n";
			}
		} else {
			echo '# return ' . PHP::dump($__result) . "\n";
			if ($content !== '') {
				echo $content . "\n";
			}
		}
	}

	/**
	 * Interactive evaluation of commands
	 *
	 * @return number
	 */
	public function interactive() {
		$this->history_file_path = $this->application->paths->uid('eval-history.log');
		$name = get_class($this->application);
		$last_exit_code = 0;
		while (true) {
			$command = $this->prompt($name . '>');
			if (feof(STDIN)) {
				echo "\nExit\n";
				return $last_exit_code;
			}
			if (empty($command)) {
				return $last_exit_code;
			}
			if ($command === 'quit' || $command === 'exit') {
				return 0;
			}
			ob_start();

			try {
				$__result = $this->_eval($command);
				$last_exit_code = 0;
			} catch (\Exception $ex) {
				$content = ob_get_clean();
				echo '# exception ' . $ex::class . "\n";
				echo '# message ' . $ex->getMessage() . "\n";
				echo "# stack trace\n" . $ex->getTraceAsString() . "\n";
				if ($content) {
					echo "# Content\n$content\n";
				}
				$this->application->hooks->call('exception', $ex);
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
	private function _before_evaluate(array $vars) {
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
			if (begins($k, '__')) {
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

	private function _prefix_commmand($string) {
		$string = trim($string, " \n\r\t;");
		$string = preg_replace('/^return\s/', '', $string);
		// If contains multiple commands, then no prefix
		if (str_contains($string, ';')) {
			return $string;
		}
		$prefix = 'return';
		if (StringTools::begins($string, [
			'echo ',
			'print ',
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
	 * @throws Exception
	 * @return mixed
	 */
	private function _eval($__string) {
		$__eval = $this->_prefix_commmand($__string);

		try {
			$command = $_ = $this;
			$application = $app = $this->application;
			extract($this->_before_evaluate(get_defined_vars()), EXTR_SKIP);
			$__eval = '?' . '><' . "?php\n$__eval;\n";
			if ($this->optionBool('debug-state')) {
				$this->verbose_log("RAW PHP EVAL: $__eval");
			}
			$__result = eval($__eval);
			$this->_after_evaluate(get_defined_vars());
			return $__result;
		} catch (\Exception $e) {
			throw $e;
		}
	}
}
