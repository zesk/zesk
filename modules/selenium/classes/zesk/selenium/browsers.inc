<?php
/**
 *
 */
namespace zesk;

/**
 * Utility class which allows filter and validation of existing capabilities.
 *
 * Based on CrossBrowserTesting structure
 *
 * @author kent
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
class Selenium_Browsers {
	/**
	 *
	 * @var Application
	 */
	protected $application = null;
	protected $os_index = array();
	protected $available = array();
	
	/**
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$this->available = $this->load_available_browsers();
		$this->build_indexes();
	}
	
	/**
	 * Load browser master list from API source
	 *
	 * @throws Exception_Configuration
	 * @return array
	 */
	private function load_available_browsers() {
		$application = $this->application;
		
		$url = $application->option("url_available_browsers");
		if ($url === "none") {
			// bypass all filtering
			return array();
		}
		if (!URL::valid($url)) {
			throw new Exception_Configuration("zesk\Application::url_available_browsers", "Not a URL {url}", compact("url"));
		}
		
		$path = $application->data_path("all_browsers.json");
		$dir = dirname($path);
		Directory::depend($dir);
		
		Net_Sync::url_to_file($application, $url, $path);
		
		return JSON::decode(file_get_contents($path));
	}
	
	/**
	 * Generate indexes and update structure to support searching
	 */
	private function build_indexes() {
		$indexes = array();
		foreach ($this->available as $system_index => $system) {
			$available_browsers[$system_index]['index'] = $system_index;
			if (array_key_exists('api_name', $system)) {
				$indexes[$system['api_name']] = $system_index;
			}
			if (array_key_exists('browsers', $system)) {
				$by_name = array();
				foreach ($system['browsers'] as $index => $browser) {
					$system['browsers']['index'] = $index;
					$by_name[$browser['api_name']] = $index;
				}
				ksort($by_name);
				$this->available[$system_index]['browsers_by_name'] = $by_name;
			}
		}
		$this->os_index = $indexes;
		ksort($this->os_index);
	}
	
	/**
	 * Given a capabilities structure, generate a unique name to identify a browser case
	 *
	 * @param array $capabilities
	 */
	private function configuration_name(array $capabilities) {
		$name = array(
			__CLASS__
		);
		foreach (to_list("os_api_name;browser_api_name") as $key) {
			if (array_key_exists($key, $capabilities)) {
				$name[] = $capabilities[$key];
			}
		}
		$name = implode(" ", $name);
		return $name;
	}
	/**
	 *
	 * @param array $available_browsers
	 * @param array $indexes
	 * @param array $capabilities
	 * @return array|null
	 */
	public function generate_best_match(array $capabilities) {
		$logger = $this->application->logger;
		/* @var $zesk \zesk\Kernel */
		$os_api_name = $browser_api_name = $screen_resolution = null;
		extract($capabilities, EXTR_IF_EXISTS);
		if ($os_api_name === null) {
			$logger->warning("No os_api_name supplied");
			return null;
		}
		if ($browser_api_name === null) {
			$logger->warning("No browser_api_name supplied");
			return null;
		}
		if (!array_key_exists($os_api_name, $this->os_index)) {
			$logger->warning("No such operating system found: {os_api_name} Choices: {choices}", $capabilities + array(
				"choices" => array_keys($this->os_index)
			));
			return null;
		}
		$index = $this->os_index[$os_api_name];
		$system = $this->available[$index];
		if (!array_key_exists('browsers', $system)) {
			$logger->warning("Missing browsers from available browser \"{name}\" (Index # {index})", $system);
			return null;
		}
		if (!array_key_exists('browsers_by_name', $system)) {
			$logger->warning("Missing browsers_by_name from available browser \"{name}\" (Index # {index})", $system);
			return null;
		}
		$by_name = $system['browsers_by_name'];
		if (!array_key_exists($browser_api_name, $by_name)) {
			$logger->warning("No such browser configuration found in OS \"{os_api_name}\", browser \"{browser_api_name}\" Choices: {choices}", $capabilities + array(
				"choices" => array_keys($by_name)
			));
			return null;
		}
		$browser = $system['browsers'][$by_name[$browser_api_name]];
		if ($screen_resolution) {
			foreach ($system['resolutions'] as $resolution) {
				if (avalue($resolution, 'default')) {
					$capabilities['screen_resolution'] = $resolution['name'];
					return $capabilities;
				}
			}
		}
		
		list($width, $height) = pair($screen_resolution, "x", null, null);
		$width = intval($width);
		$height = intval($height);
		
		$closest_distance = null;
		$closest = null;
		foreach ($system['resolutions'] as $resolution) {
			if ($resolution['name'] === $screen_resolution) {
				return $capabilities;
			}
			$distance = pow(abs($resolution['width'] - $width), 2) + pow(abs($resolution['height'] - $height), 2);
			if ($closest === null || $distance < $closest_distance) {
				$closest = $resolution;
				$closest_distance = $distance;
			}
		}
		$logger->warning("No such browser resolution {screen_resolution} found in OS {os_api_name}, browser {browser_api_name}, using found resolution {found_resolution}", $capabilities + array(
			'found_resolution' => $closest['name']
		));
		$capabilities['screen_resolution'] = $closest['name'];
		return $capabilities;
	}
	
	/**
	 *
	 * @param array $desired_browsers
	 * @return array
	 */
	public function browsers_clean_and_fix(array $desired_browsers) {
		$result = array();
		/* @var $zesk \zesk\Kernel */
		foreach ($desired_browsers as $index => $record) {
			$caps = avalue($record, "desiredCapabilities", array());
			$name = self::configuration_name($caps);
			if (!array_key_exists('name', $caps)) {
				$caps['name'] = $name;
			}
			if (count($this->available) > 0) {
				// Only if we have a list of available browsers should we attempt to correct the capabilities
				$caps = $this->generate_best_match($caps);
				if ($caps === null) {
					$this->application->logger->warning("Skipping configuration {name} - no matching browser configuration found (Record # {index})", compact("name", "index"));
					continue;
				}
			}
			if (!array_key_exists('browserName', $record)) {
				$record['browserName'] = $caps['browserName'];
			}
			if (!array_key_exists('name', $record)) {
				$record['name'] = $name;
			}
			$record['desiredCapabilities'] = $caps;
			
			$result[] = $record;
		}
		return $result;
	}
}
