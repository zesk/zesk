<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Selenium;

use zesk\Exception_Configuration;

/**
 * @author kent
 *
 */
class TestCase extends \zesk\PHPUnit_TestCase {
	protected array $load_modules = [
		'selenium/php-webdriver-facebook',
	];

	/**
	 * Debuggin
	 *
	 * @var boolean
	 */
	protected $debug = true;

	/**
	 *
	 * @var RemoteWebDriver
	 */
	protected $driver = null;

	/**
	 * Desired browser capabilities
	 *
	 * @var array
	 */
	protected $capabilities = [
		'browserName' => 'firefox',
	];

	/**
	 * Connection timeout
	 *
	 * @var integer
	 */
	protected $timeout = 5000;

	/**
	 *
	 */
	public function cleanup(): void {
		$this->close();
	}

	/**
	 * Open a new session
	 *
	 * @param array $capabilities
	 * @param int $timeout
	 * @throws Exception_Configuration
	 * @return RemoteWebDriver
	 */
	final public function open(array $capabilities = null, $timeout = null) {
		if (!$this->driver) {
			$webdriver_url = $this->option('url');
			if (!$webdriver_url) {
				$host = $this->option('host');
				if (!$host) {
					throw new Exception_Configuration('{class}::host or ::url needs to be set to a Selenium server', [
						'class' => get_class($this),
					]);
				}
				$webdriver_url = "http://$host:4444/wd/hub";
			}
			if ($capabilities === null) {
				$capabilities = $this->capabilities;
			}
			if ($timeout === null) {
				$timeout = $this->timeout;
			}
			if ($this->debug) {
				$this->application->logger->debug('Connecting to {webdriver_url} {capabilities} {timeout}', compact('webdriver_url', 'capabilities', 'timeout'));
			}
			$this->driver = \RemoteWebDriver::create($webdriver_url, $capabilities, $timeout);
		}
		return $this->driver;
	}

	/**
	 * Close a shutdown session
	 *
	 * @return Test_Selenium
	 */
	final public function close() {
		if ($this->driver) {
			if ($this->driver->getCommandExecutor() !== null) {
				$this->driver->quit();
				$this->driver = null;
			}
		}
		return $this;
	}
}
