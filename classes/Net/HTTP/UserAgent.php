<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_HTTP_UserAgent {
	/**
	 * User agent raw string
	 *
	 * @var string
	 */
	protected $user_agent = null;

	/**
	 * Parsed results from user_agent_test
	 *
	 * @var array
	 */
	protected $is = null;

	/**
	 * Parsed results from user_agent_test
	 *
	 * @var array
	 */
	protected $classify = null;

	/**
	 * Language equivalents of the classification names
	 *
	 * @var array
	 */
	private static $classifications_names = [
		'platform' => 'Platform',
		'browser' => 'Web Browser',
		'mobile' => 'Mobile/Desktop',
		'user_agent' => 'User Agent',
	];

	/**
	 * Presence of these true values in ->is will display classification
	 * Last array item in each is the default
	 *
	 * @var array
	 */
	private static $classifications = [
		'platform' => [
			'mac' => 'mac',
			'windows' => "windows",
			'linux' => "linux",
			'iphone' => 'iphone',
			'ipad' => 'ipad',
			'android' => 'android',
			'' => 'unknown',
		],
		'browser' => [
			'safari' => 'safari',
			'chrome' => 'chrome',
			'ie' => 'ie',
			'firefox' => 'firefox',
			'' => 'unknown',
		],
		'mobile' => [
			'mobile' => 'mobile',
			'' => 'desktop',
		],
	];

	/**
	 *
	 * @var array
	 */
	private static $lang_classifications = [
		'mac' => 'Mac OS X',
		'windows' => "Windows",
		'linux' => "Linux",
		'iphone' => 'iPhone',
		'ipad' => 'iPad',
		'android' => 'Android',
		'unknown' => 'Unknown',
		'safari' => 'Safari',
		'chrome' => 'Chrome',
		'ie' => 'Internet Explorer',
		'firefox' => 'Firefox',
		'mobile' => 'Mobile device',
		'desktop' => 'Desktop',
		'platform' => 'Platform',
		'browser' => 'Web Browser',
	];

	/**
	 * Create a new user agent
	 *
	 * @param string $user_agent
	 */
	public function __construct($user_agent = null) {
		$this->user_agent($user_agent);
	}

	/**
	 *
	 * @param string $user_agent
	 * @return self
	 */
	public static function factory($user_agent = null) {
		return new self($user_agent);
	}

	/**
	 *
	 */
	public function __wakeup(): void {
		$this->is = null;
		$this->classify = null;
	}

	/**
	 *
	 * @return string[]
	 */
	public function __sleep() {
		return [
			"user_agent",
		];
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return PHP::dump($this->user_agent);
	}

	/**
	 * Get/set user agent
	 *
	 * @param string $set
	 * @return self|string
	 */
	public function user_agent($set = null) {
		if ($set !== null) {
			$this->user_agent = strval($set);
			$this->is = null;
			$this->classify = null;
			return $this;
		}
		return $this->user_agent;
	}

	/**
	 * Passes criteria passed in?
	 *
	 * @param string $criteria
	 * @return mixed|array
	 */
	public function is($criteria = null) {
		if (!is_array($this->is)) {
			$this->is = self::parse($this->user_agent);
		}
		if ($criteria === null) {
			return $this->is;
		}
		return avalue($this->is, $criteria, false);
	}

	/**
	 * Classify user agent and return classificiation
	 *
	 * @param string $translate
	 */
	public function classify($translate = false) {
		if (!is_array($this->classify)) {
			$this->classify = $this->_classify();
		}
		return $translate ? ArrayTools::map_keys(ArrayTools::map_values($this->classify, self::$lang_classifications), self::$lang_classifications) : $this->classify;
	}

	/**
	 * Check what the current browser is.
	 * Generally, this is discouraged, but it makes sense in a few cases - generally if you are
	 * downloading
	 * software which is platform-specific and want to present that one first (or only), or if you
	 * need to give instructions to the user and it's different
	 * for each browser or platform. (Ctrl vs. Command comes to mind when offering key
	 * equivalents...)
	 *
	 * The array returned has the following values:
	 *
	 * - opera - The Opera browser. http://opera.com/
	 * - iphone - A browser running on an iPhone. (Probably Safari - but I wouldn't assume)
	 * - ipad - A browser running on an iOS. (Probably Safari - but I wouldn't assume)
	 * - ios - A browser running iOS. (Probably Safari - but I wouldn't assume)
	 * - ios5 - A browser running iOS5
	 * - ios6 - A browser running iOS6
	 * - ios7 - A browser running iOS7
	 * - webkit - A webkit-based browser (Chrome, Safari on iOS systems)
	 * - chrome - Google's Chrome browser.
	 * - ie - Internet Explorer on Windows or mobile devices.
	 * - ie6/ie7/ie8/ie9/ie10 - Specific version if IE.
	 * - firefox - The FireFox browser.
	 * - safari - Safari browser from Apple (any platform - including Windows.)
	 * - mac - A browser running on a Macintosh computer running Mac OS.
	 * - linux - A browser running on the Linux operating system.
	 * - windows - A browser running on a Microsoft Windows system
	 * - mac_intel - Intel-based Mac
	 * - mac_ppc - PPC-based Mac (dinosaur)
	 * - string - The original string passed into this function
	 *
	 * @param string $check
	 *        	Optional. Check for a specific setting in the user agent.
	 * @param string $user_agent
	 *        	Optional. The user agent to check. If unspecified, check the
	 *        	$_SERVER['HTTP_USER_AGENT'].
	 * @return array string
	 */
	public static function parse($user_agent) {
		// Samples @todo Move to test
		//
		// 2012-11-08
		// Chrome on MacOS X
		//     Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11
		// Safari on MacOS X
		//     Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/536.26.17 (KHTML, like Gecko) Version/6.0.2 Safari/536.26.17
		// Firefox on MacOS X
		//     Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0
		// Firefox on Windows XP
		//     Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.8) Gecko/20100202 Firefox/3.5.8 (.NET CLR 3.5.30729)
		// Safari on Windows XP
		//     Mozilla/5.0 (Windows NT 5.1) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2
		// Opera on Mac OS X
		//     Opera/9.80 (Macintosh; Intel Mac OS X 10.7.5; U; en) Presto/2.10.289 Version/12.02
		$result['user_agent'] = $user_agent;

		$result['string'] = $ua = strtolower($user_agent);

		$result['opera'] = (str_contains($ua, "opera"));

		$result['iphone'] = (str_contains($ua, "iphone"));
		$result['ipad'] = (str_contains($ua, "ipad"));
		$result['ios'] = $result['iphone'] || $result['ipad'] || (str_contains($ua, "ios"));
		foreach (to_list("5;6;7;8;9;10") as $v) {
			$result["ios${v}"] = $result['ios'] && (str_contains($ua, "os ${v}_"));
		}
		$result['webkit'] = str_contains($ua, "applewebkit")  ;
		$result['chrome'] = str_contains($ua, "chrome/")  ;

		$result['ie10'] = !$result['opera'] && (str_contains($ua, "msie 10"));
		$result['ie9'] = !$result['opera'] && (str_contains($ua, "msie 9"));
		$result['ie8'] = !$result['opera'] && (str_contains($ua, "msie 8"));
		$result['ie7'] = !$result['opera'] && (str_contains($ua, "msie 7")) && !$result['ie8'];
		$result['ie6'] = !$result['opera'] && (str_contains($ua, "msie 6")) && !$result['ie7'] && !$result['ie8'];
		$result['ie'] = !$result['opera'] && (str_contains($ua, "msie"));

		$result['kindle'] = (str_contains($ua, "kindle"));
		$result['surface'] = (str_contains($ua, "surface"));

		$result['firefox'] = (str_contains($ua, "firefox"));
		$result['safari'] = (str_contains($ua, "safari")) && !$result['chrome'];
		$result['mac'] = (str_contains($ua, "macintosh"));

		$result['linux'] = (str_contains($ua, "linux"));
		$result['windows'] = (str_contains($ua, "windows"));
		$result['mac_intel'] = $result['mac'] && (str_contains($ua, "intel"));
		$result['mac_ppc'] = $result['mac'] && (!str_contains($ua, "intel"));

		$result['mobile'] = $result['ios'] || (str_contains($ua, "mobile"));
		$result['phone'] = $result['iphone'];
		$result['tablet'] = !$result['phone'] && ($result['ipad'] || $result['kindle'] || $result['surface']);
		$result['desktop'] = !$result['phone'] && !$result['tablet'];

		return $result;
	}

	/**
	 * Classify user agent
	 *
	 * @return unknown[]
	 */
	private function _classify() {
		$result = [];
		foreach (self::$classifications as $type => $tests) {
			foreach ($tests as $check => $value) {
				if ($check === '' || $this->is($check)) {
					$result[$type] = $value;

					break;
				}
			}
		}
		return $result;
	}
}
