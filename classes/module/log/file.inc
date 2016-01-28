<?php

class Module_Log_File extends Module_Log {

	const FATAL = log::FATAL;
	const ERROR = log::ERROR;
	const WARNING = log::WARNING;
	const NOTICE = log::NOTICE;
	const DEBUG = log::DEBUG;
	const ALL = log::ALL;

	/**
	 * Set to true to debug/trace log::file commands to stdout
	 * @var unknown
	 */
	public static $debug_file = false;

	/**
	 * Current log file pointer
	 *
	 * @var resource
	 */
	private $fp = null;

	/**
	 * Current file setting.
	 *
	 * @var string
	 */
	private $file = null;

	/**
	 * File name contains substitutions
	 *
	 * @var string
	 */
	private $file_rollover = false;

	/**
	 * Default mode for log files
	 *
	 * @var string
	 * @see fopen
	 */
	private $mode = "a";

	/**
	 *
	 * @return Module_Log_File
	 */
	private static function singleton() {
		return Module::object('Log_File');
	}

	public function initialize() {
		zesk::add_hook('exit', array(
			$this,
			'clean_up'
		));
	}
	/**
	 * Default internal logging hook
	 *
	 * @param array $log
	 */
	public function hook_log_send(array $log) {
		if (!$this->fp) {
			return;
		}
		$level = $this->option('level', self::ALL);
		if ($log['_level'] > $level) {
			return;
		}
		$prefix = map("{_level_string} {_module} {_date} {_time}:{_pid}: ", $log);
		$message = $prefix . ltrim(text::indent($log['_formatted'], strlen($prefix), false, " "));
		self::open();
		if (is_resource($this->fp)) {
			fwrite($this->fp, $message);
			fflush($this->fp);
		}
	}

	/**
	 * Close log file
	 */
	private function clean_up_fp() {
		if (is_resource($this->fp)) {
			fclose($this->fp);
			$this->fp = null;
		}
	}

	/**
	 * Implements hook Module::configured
	 *
	 * Loads global values which affect logging options
	 */
	public function hook_configured() {
		if ($this->option('debug_file')) {
			$this->debug_file = false;
		}
		$f = zesk::get1('Module_Log_File::file;log::file;log::target');
		if (!empty($f) && ($f !== $this->file)) {
			$this->file($f);
		}
	}

	/**
	 * Close log file on exit
	 */
	public function clean_up() {
		$this->clean_up_fp();
	}

	/**
	 * Implements Module::hooks to register this Module's hooks
	 */
	public static function hooks() {
		zesk::add_hook('Application::configured', __CLASS__ . '::configured');
	}

	public static function map($string) {
		return Timestamp::now()->format($string);
	}

	private function open() {
		if (empty($this->file)) {
			return null;
		}
		if ($this->file_rollover !== ($newfile = self::map($this->file))) {
			$this->clean_up_fp();
			$this->file_rollover = $newfile;
		}
		if (is_resource($this->fp)) {
			return $this->fp;
		}
		$this->fp = @fopen($this->file_rollover, $this->mode);
		if (is_resource($this->fp)) {
			if ($this->debug_file) {
				echo "Opened " . $this->file_rollover . " with mode " . $this->mode . ".\n";
			}
			return $this->fp;
		}
		$dir = dirname($this->file_rollover);
		if (zesk::getb('log::create_path', true) && !is_dir($dir)) {
			dir::depend($dir);
			$this->fp = @fopen($this->file_rollover, $this->mode);
			if (is_resource($this->fp)) {
				if ($this->debug_file) {
					echo "Opened " . $this->file_rollover . " with mode " . $this->mode . " - after making parent directories: " . $dir . ".\n";
				}
				return $this->fp;
			}
		}
		throw new Exception_File_Permission($this->file_rollover, 'Can not open "{filename}" using mode "{mode}"', array(
			"mode" => $this->mode
		));
	}

	/**
	 * Get/Set file, filename or disable file logging
	 *
	 * @param string $filename
	 * @param string $mode
	 * @throws Exception_File_Permission
	 * @return string NULL
	 */
	public function file($filename = false, $mode = "a") {
		if ($this->debug_file) {
			echo "log::file(" . php::dump($filename) . "," . php::dump($mode) . ")\n";
		}
		if ($filename === false) {
			return $this->file;
		}
		if ($filename === null) {
			$this->clean_up_fp();
			$this->file = null;
			$this->file_rollover = null;
			return null;
		}
		if (is_resource($filename)) {
			$this->fp = $filename;
			$this->file = "";
			$this->file_rollover = "";
			return $this->fp;
		}
		if (!file::is_absolute($filename)) {
			$filename = $this->application->application_root($filename);
		}
		$this->file = $filename;
		$this->mode = $mode;
		return $this->open();
	}
}