<?php
/**
 * 
 */
use Psr\Log\LogLevel;
use zesk\arr;
use zesk\File;
use zesk\Kernel;
use zesk\Module;
/**
 * 
 * @see zesk\Logger\File
 */
class Module_Logger_File extends Module {
	/**
	 * 
	 * @var array
	 */
	protected $fps = array(
		LogLevel::EMERGENCY,
		LogLevel::ALERT,
		LogLevel::CRITICAL,
		LogLevel::ERROR,
		LogLevel::WARNING,
		LogLevel::NOTICE,
		LogLevel::INFO,
		LogLevel::DEBUG
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function hook_configured() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		if ($zesk->configuration->path_exists("log::file")) {
			$this->_legacy_configuration($zesk);
		}
		$defaults = $this->option_array("defaults");
		$defaults = arr::remove($defaults, array(
			"name",
			"linkname"
		));
		$files = $this->option_array("files");
		foreach ($files as $name => $settings) {
			$settings += $defaults;
			if (!isset($settings['name'])) {
				$zesk->logger->error(__CLASS__ . "::files::$name is missing name key");
				continue;
			}
			$filename = $this->_filename_path($settings['name']);
			$levels = isset($settings['level']) ? $settings['level'] : null;
			$handler = new zesk\Logger\File($filename, $settings);
			$zesk->logger->register_handler($name, $handler, $levels);
		}
	}
	
	/**
	 * 
	 * @param string $filename
	 * @return string
	 */
	private function _filename_path($filename) {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		if (!File::is_absolute($filename)) {
			$filename = $zesk->paths->application($filename);
		}
		return $filename;
	}
	/**
	 * @deprecated 2016-09
	 */
	private function _legacy_configuration(Kernel $zesk) {
		$zesk->deprecated("log:file/log::level configuration option is deprecated");
		$level = $zesk->configuration->path_get("log::level", LogLevel::ERROR);
		$level = avalue(array(
			0 => LogLevel::CRITICAL,
			1 => LogLevel::ERROR,
			2 => LogLevel::WARNING,
			3 => LogLevel::NOTICE,
			4 => LogLevel::DEBUG
		), $level, $level);
		if (is_numeric($level) && $level > 4) {
			$level = LogLevel::DEBUG;
		}
		$filename = $this->_filename_path($zesk->configuration->log->file);
		$file = new zesk\Logger\File($filename);
		$levels = array();
		foreach ($this->fps as $fp_level) {
			$levels[] = $fp_level;
			if ($fp_level === $level) {
				break;
			}
		}
		$zesk->logger->register_handler("log::file", $file, $levels);
	}
}
