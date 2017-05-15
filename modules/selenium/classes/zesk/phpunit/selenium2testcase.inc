<?php
/**
 * 
 */
namespace zesk;

/**
 *
 * @todo update Selenium2TestCase
 * @author kent
 *
 */
abstract class PHPUnit_Selenium2TestCase extends PHPUnit_Extensions_Selenium2TestCase {
	protected function waitForId($selector, $timeout = null) {
		$this->log("Waiting for ID: {selector} ({timeout} secs)", compact("selector", "timeout"));
		return $this->waitForFunc("byId", $selector, $timeout);
	}
	protected function waitForCssSelector($selector, $timeout = null) {
		$this->log("Waiting for CSS Selector: {selector} ({timeout} secs)", compact("selector", "timeout"));
		return $this->waitForFunc("byCssSelector", $selector, $timeout);
	}
	protected function waitForName($selector, $timeout = null) {
		$this->log("Waiting for Name: {selector} ({timeout} secs)", compact("selector", "timeout"));
		return $this->waitForFunc("byName", $selector, $timeout);
	}
	private function waitForFunc($func, $selector, $timeout = null) {
		if ($timeout === null) {
			$timeout = 10;
		}
		$timer = new Timer();
		while ($timer->elapsed() < $timeout) {
			try {
				$result = $this->$func($selector);
				return $result;
			} catch (\Exception $e) {
				sleep(1);
			}
		}
		throw new Exception_NotFound("{class}:waitFor:{func} Element {selector} not found on page {url}", array(
			"func" => $func,
			"class" => get_class($this),
			"method" => __METHOD__,
			"selector" => $selector,
			"url" => $this->getBrowserUrl()
		));
	}
	protected function log($message, array $args = array()) {
		fwrite(STDOUT, map($message, $args) . "\n");
		//echo map($message, $args);
	}
	protected function browser_config_string() {
		$name = array(
			$this->getBrowser()
		);
		$caps = $this->getDesiredCapabilities();
		foreach (to_list("os_api_name;browser_api_name;screen_resolution") as $key) {
			if (array_key_exists($key, $caps)) {
				$name[] = $caps[$key];
			}
		}
		return implode(" ", $name);
	}
	
	/**
	 * Details for error messages to determine browser context
	 */
	protected function message_details($mixed = null) {
		if (is_string($mixed)) {
			$mixed = array(
				"message" => $mixed
			);
		}
		$details = array(
			"browser" => $this->getBrowser(),
			"url" => $this->url()
		);
		if (is_array($mixed)) {
			$details += $mixed;
		}
		return Text::format_pairs($details);
	}
	
	/**
	 * Request a page out-of-band without cookies, and parse as JSON
	 *
	 * @param string $url
	 * @return mixed
	 */
	public function json_request($url) {
		$this->assertTrue(URL::valid($url), "URL::valid(\"$url\") is not TRUE");
		//echo "Fetching $url ...\n";
		$result = file_get_contents($url);
		//echo "==\n$result\n==\n";
		return JSON::decode($result);
	}
	
	/**
	 *
	 * @param unknown $method
	 */
	protected function begin($method) {
		$this->log("Beginning $method for configuration " . $this->browser_config_string());
	}
	
	/**
	 *
	 * @param unknown $method
	 */
	protected function end($method) {
		$this->log("Completed $method for configuration " . $this->browser_config_string());
	}
	
	/**
	 * Retrieve a part of the current browser URL
	 *
	 * @param string $part
	 * @return string|null
	 */
	public function url_part($part) {
		$parts = URL::parse($this->url());
		return avalue($parts, $part);
	}
	protected function check_page_source() {
		$source = $this->source();
		foreach (array(
			"PHP-ERROR"
		) as $error_string) {
			$this->assertTrue(strpos($source, $error_string) === false, $this->message_details("$error_string found in page source"));
		}
		return true;
	}
	private $saved_url = null;
	
	/**
	 * Intercept all new URLs and check for page errors
	 * 
	 * {@inheritDoc}
	 * @see PHPUnit_Extensions_Selenium2TestCase::url($url)
	 */
	public function url($url = null, $check = true) {
		if ($url === null) {
			// This may bite us later
			if ($this->saved_url) {
				return $this->saved_url;
			}
			return $this->saved_url = $this->__call("url", array());
		} else {
			$this->saved_url = null;
			$this->log("Going to {url}", compact("url"));
			$result = $this->__call("url", array(
				$url
			));
			if ($check) {
				$this->check_page_source();
			}
			return $result;
		}
	}
	/**
	 * Add a path to the existing home URL
	 *
	 * @param string $path
	 * @return string
	 */
	public function add_path($path) {
		return glue($this->url_default(), "/", $path);
	}
	
	/**
	 * Go to a browser path
	 * 
	 * @param string $path Relative path to go to
	 * @param boolean $check Check the URL after loading for page errors 
	 */
	public function url_path($path, $check = true) {
		$this->url($this->add_path($path), $check);
	}
	protected function assert_source_contains($substring) {
		$source = $this->source();
		$this->assertTrue(strpos($source, $substring) !== false, $this->message_details("Source does not contain substring \"$substring\""));
	}
	/**
	 * 
	 * @param string $path
	 */
	protected function assert_path($path) {
		$this->assertEquals($this->url_part("path"), $path, $this->message_details("assert_path = $path"));
	}
	
	/**
	 * The default URL (home page) for this site
	 * 
	 * @return string URL
	 */
	abstract function url_default();
}
