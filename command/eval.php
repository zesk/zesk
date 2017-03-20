<?php
/**
 * 
 */
namespace zesk;

/**
 * Run arbitrary PHP code in the application context.
 *
 * @category Management
 */
class Command_Eval extends Command_Base {
	protected $option_types = array(
		'json' => 'boolean',
		'interactive' => 'boolean',
		'*' => 'string'
	);
	protected $option_chars = array(
		'i' => 'interactive'
	);
	function run() {
		while ($this->has_arg()) {
			$arg = $this->get_arg("eval");
			if ($arg === "--") {
				return 0;
			}
			$this->verbose_log("Evaluating: $arg\n");
			$this->_eval($arg);
		}
		if ($this->option_bool('interactive')) {
			return $this->interactive();
		}
	}
	
	/**
	 * Interactive evaluation of commands
	 * 
	 * @return number
	 */
	public function interactive() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		$this->history_file_path = $zesk->paths->uid("eval-history.log");
		$name = $zesk->application_class;
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
			if ($command === "quit" || $command === "exit") {
				return 0;
			}
			ob_start();
			try {
				$result = $this->_eval($command);
				$last_exit_code = 0;
			} catch (Exception $ex) {
				$content = ob_get_clean();
				echo "# exception " . get_class($ex) . "\n";
				echo "# message " . $ex->getMessage() . "\n";
				echo "# stack trace\n" . $ex->getTraceAsString() . "\n";
				if ($content) {
					echo "# Content\n$content\n";
				}
				$this->application->hooks->call("exception", $ex);
				$last_exit_code = 99;
				continue;
			}
			$content = ob_get_clean();
			
			if ($result === null) {
				if ($content !== "") {
					echo $content . "\n";
				} else {
				}
			} else {
				echo "# return " . PHP::dump($result) . "\n";
				if ($content !== "") {
					echo $content . "\n";
				}
			}
		}
	}
	private function _eval($string) {
		$string = trim($string, " \n\r\t;");
		$string = preg_replace('/^return\s/', '', $string);
		$prefix = "return";
		if (str::begins($string, array(
			'echo ',
			'print '
		)) || strpos($string, ";") !== false) {
			$prefix = "";
		}
		try {
			$result = eval("?" . "><" . "?php\n$prefix " . $string . ";\n");
			return $result;
		} catch (Exception $e) {
			throw $e;
		}
	}
}

