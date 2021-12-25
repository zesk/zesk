<?php declare(strict_types=1);
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
	protected $fps = [
		LogLevel::EMERGENCY,
		LogLevel::ALERT,
		LogLevel::CRITICAL,
		LogLevel::ERROR,
		LogLevel::WARNING,
		LogLevel::NOTICE,
		LogLevel::INFO,
		LogLevel::DEBUG,
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		/**
		 * We want to load first so we get logging as soon as possible, use system configured hook, not module or application
		 */
		$this->application->hooks->add(Hooks::HOOK_CONFIGURED, [
			$this,
			"configured",
		], "first");
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function configured(): void {
		/* @var $zesk Kernel */
		$defaults = $this->option_array("defaults");
		$defaults = ArrayTools::remove($defaults, [
			"name",
			"linkname",
		]);
		$files = $this->option_array("files");
		$names = [];
		foreach ($files as $name => $settings) {
			$settings += $defaults;
			if (!isset($settings['name'])) {
				$this->application->logger->error(__CLASS__ . "::files::{name} is missing name key", [
					"name" => $name,
				]);

				continue;
			}
			$filename = $this->_filename_path($settings['name']);
			$levels = $settings['level'] ?? null;
			$handler = new Logger\File($filename, $settings);
			$this->application->logger->register_handler($name, $handler, $levels);
			$names[] = $name;
		}
		if ($this->debug) {
			$this->application->logger->debug("{method} invoked, {names} handlers registered", [
				"method" => __METHOD__,
				"names" => $names,
			]);
		}
	}

	/**
	 *
	 * @param string $filename
	 * @return string
	 */
	private function _filename_path($filename) {
		if (!File::is_absolute($filename)) {
			$filename = $this->application->path($filename);
		}
		return $filename;
	}
}
