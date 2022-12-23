<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Selenium;

use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_NotFound;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	/**
	 *
	 * @var string
	 */
	private $lib_path = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		$this->lib_path = $this->application->path('vendor/php-webdriver-facebook');
		if (!is_dir($this->lib_path)) {
			throw new Exception_Directory_NotFound($this->lib_path, 'Initializing selenium/php-webdriver-facebook');
		}
		$init = path($this->lib_path, '__init__.php');
		if (!is_file($init)) {
			throw new Exception_File_NotFound($init, 'Initializing selenium/php-webdriver-facebook');
		}
	}
}
