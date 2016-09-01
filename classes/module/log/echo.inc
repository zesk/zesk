<?php
class Module_Log_Echo extends Module_Log {
	public $stderr = null;
	public function initialize() {
		$this->application->hooks->add('configured', array(
			$this,
			'configured'
		));
	}
	public function configured() {
		if (!$this->disabled()) {
			if ($this->option_bool("debug")) {
				log::debug("Echo log enabled");
			}
		}
	}
	public function disabled($set = null) {
		if (is_bool($set)) {
			$this->set_option('disabled', $set);
			return $this;
		}
		return $this->option_bool('disabled');
	}
	/**
	 * Default internal logging hook
	 *
	 * @param array $log
	 */
	public function hook_log_send(array $log) {
		$level = $this->option('level', self::ALL);
		if ($log['_level'] > $level) {
			return;
		}
		if ($log['_level'] <= self::ERROR) {
			if (!is_resource($this->stderr)) {
				$this->stderr = fopen("php://stderr", "w");
			}
			$formatted = rtrim($log['_formatted'], "\n");
			fwrite($this->stderr, "$formatted\n");
		} else {
			if ($this->disabled()) {
				return;
			}
			$prefix = map("{_level_string} {_module} {_date} {_time}:{_pid}: ", $log);
			$result = rtrim($prefix . ltrim(text::indent($log['_formatted'], strlen($prefix), false, " ")), "\n");
			echo $result . "\n";
		}
	}
}