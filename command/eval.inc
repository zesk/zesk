<?php
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
				return;
			}
			$this->verbose_log("Evaluating: $arg\n");
			$this->_eval($arg);
		}
		if ($this->option_bool('interactive')) {
			return $this->interactive();
		}
	}
	public function interactive() {
		$this->history_file_path = zesk::uid_path("eval-history.log");
		$name = zesk::application_class();
		while (true) {
			$command = $this->prompt($name . '>');
			if (feof(STDIN)) {
				echo "\nExit\n";
				return 0;
			}
			if (empty($command)) {
				return 0;
			}
			if ($command === "quit" || $command === "exit") {
				return 0;
			}
			ob_start();
			try {
				$result = $this->_eval($command);
			} catch (Exception $ex) {
				$content = ob_get_clean();
				echo "# exception " . get_class($ex) . "\n";
				echo "# message " . $ex->getMessage() . "\n";
				echo "# stack trace\n" . $ex->getTraceAsString() . "\n";
				if ($content) {
					echo "# Content\n$content\n";
				}
				$this->application->hooks->call("exception", $ex);
				continue;
			}
			$content = ob_get_clean();
			
			if ($result === null) {
				if ($content !== "") {
					echo $content . "\n";
				} else {
				}
			} else {
				echo "# return " . php::dump($result) . "\n";
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

