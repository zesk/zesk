<?php
/**
 * 
 */
namespace zesk;

use Psr\Log\LogLevel;

/**
 * 
 * @see Logger\File
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
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		/**
		 * We want to load first so we get logging as soon as possible, use system configured hook, not module or application
		 */
		$this->application->hooks->add(Hooks::hook_configured, array(
			$this,
			"configured"
		), "first");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function configured() {
		/* @var $zesk Kernel */
		$defaults = $this->option_array("defaults");
		$defaults = arr::remove($defaults, array(
			"name",
			"linkname"
		));
		$files = $this->option_array("files");
		$names = array();
		foreach ($files as $name => $settings) {
			$settings += $defaults;
			if (!isset($settings['name'])) {
				$this->application->logger->error(__CLASS__ . "::files::{name} is missing name key", array(
					"name" => $name
				));
				continue;
			}
			$filename = $this->_filename_path($settings['name']);
			$levels = isset($settings['level']) ? $settings['level'] : null;
			$handler = new Logger\File($filename, $settings);
			$this->application->logger->register_handler($name, $handler, $levels);
			$names[] = $name;
		}
		if ($this->debug) {
			$this->application->logger->debug("{method} invoked, {names} handlers registered", array(
				"method" => __METHOD__,
				"names" => $names
			));
		}
	}
	
	/**
	 * 
	 * @param string $filename
	 * @return string
	 */
	private function _filename_path($filename) {
		global $zesk;
		/* @var $zesk Kernel */
		if (!File::is_absolute($filename)) {
			$filename = $zesk->paths->application($filename);
		}
		return $filename;
	}
	/**
	 * @deprecated 2016-09
	 */
	private function _legacy_configuration() {
		$app = $this->application;
		$this->zesk->deprecated("log:file/log::level configuration option is deprecated");
		$level = $app->configuration->path_get("log::level", LogLevel::ERROR);
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
		$filename = $this->_filename_path($app->configuration->log->file);
		$file = new Logger\File($filename);
		$levels = array();
		foreach ($this->fps as $fp_level) {
			$levels[] = $fp_level;
			if ($fp_level === $level) {
				break;
			}
		}
		$app->logger->register_handler("log::file", $file, $levels);
	}
}
