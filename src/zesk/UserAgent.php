<?php
declare(strict_types=1);
/**
 * Browser user agent specification. Simplistic and uses simple pattern matching and boolean logic.
 *
 * Look at browscap.ini and related services.
 *
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @see UserAgentTest
 * @author kent
 *
 */
class UserAgent
{
	/**
	 * User agent raw string
	 *
	 * @var string
	 */
	protected string $userAgent;

	/**
	 * Parsed results from user_agent_test
	 *
	 * @var array
	 */
	private array $is = [];

	/**
	 * Parsed results from user_agent_test
	 *
	 * @var array
	 */
	private array $classify = [];

	/**
	 * Language equivalents of the classification names
	 *
	 * @var array
	 */
	private static array $langClassificationNames = [
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
	private static array $classifications = [
		'platform' => [
			'mac' => 'mac',
			'windows' => 'windows',
			'linux' => 'linux',
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
	private static array $langClassifications = [
		'mac' => 'Mac OS X',
		'windows' => 'Windows',
		'linux' => 'Linux',
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
	public function __construct(string $user_agent = '')
	{
		$this->setUserAgent($user_agent);
	}

	/**
	 *
	 * @param string $user_agent
	 * @return self
	 */
	public static function factory(string $user_agent = ''): self
	{
		return new self($user_agent);
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return PHP::dump($this->userAgent);
	}

	public function userAgent(): string
	{
		return $this->userAgent;
	}

	/**
	 * Set user agent
	 *
	 * @param string $set
	 * @return self
	 */
	public function setUserAgent(string $set): self
	{
		$this->userAgent = $set;
		$this->is = [];
		$this->classify = [];
		return $this;
	}

	/**
	 * Passes criteria passed in?
	 *
	 * @param string $criteria
	 * @return bool
	 */
	public function is(string $criteria): bool
	{
		return $this->attributes()[$criteria] ?? false;
	}

	/**
	 * Classify user agent and return classification
	 */
	public function classify(): array
	{
		if (count($this->classify) === 0) {
			$this->classify = $this->_classify();
		}
		return $this->classify;
	}

	/**
	 * Return all attributes
	 * @return array
	 */
	public function attributes(): array
	{
		if (count($this->is) === 0) {
			$this->is = self::parse($this->userAgent);
		}
		return $this->is;
	}

	/**
	 * Fetch the English classification strings
	 *
	 * @return array
	 */
	public function classifyEN(): array
	{
		return ArrayTools::keysMap(ArrayTools::valuesMap($this->classify(), self::$langClassifications), self::$langClassificationNames);
	}

	/**
	 * Run tests against the current user agent.
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
	 * - ios8 - A browser running iOS8
	 * - ios9 - A browser running iOS9
	 * - ios10 - A browser running iOS10
	 * - webkit - A webkit-based browser (Chrome, Safari on iOS systems)
	 * - chrome - Google's Chrome browser.
	 * - kindle - Kindle tablet
	 * - chrome - Google's Chrome browser.
	 * - ie - Internet Explorer on Windows or mobile devices.
	 * - ie6/ie7/ie8/ie9/ie10 - Specific version of IE.
	 * - firefox - The FireFox browser.
	 * - safari - Safari browser from Apple (any platform - including Windows.)
	 * - mac - A browser running on a Macintosh computer running macOS.
	 * - linux - A browser running on the Linux operating system.
	 * - windows - A browser running on a Microsoft Windows system
	 * - macIntel - Intel-based Mac
	 * - macPPC - PPC-based Mac (dinosaur)
	 * - userAgent - The original string passed into this function
	 * - lowUserAgent - The original string passed into this function, lowercase.
	 *
	 * @param string $userAgent
	 * @return array string
	 */
	public static function parse(string $userAgent): array
	{
		$result['userAgent'] = $userAgent;

		$result['lowUserAgent'] = $ua = strtolower($userAgent);

		$result['opera'] = (str_contains($ua, 'opera'));

		$result['iphone'] = (str_contains($ua, 'iphone'));
		$result['ipad'] = (str_contains($ua, 'ipad'));
		$result['ios'] = $result['iphone'] || $result['ipad'] || (str_contains($ua, 'ios'));
		foreach (['5', '6', '7', '8', '9', '10'] as $v) {
			$result["ios$v"] = $result['ios'] && (str_contains($ua, "os ${v}_"));
		}
		$result['webkit'] = str_contains($ua, 'apple' . 'webkit');
		$result['chrome'] = str_contains($ua, 'chrome/');

		$result['ie10'] = !$result['opera'] && (str_contains($ua, 'msie 10'));
		$result['ie9'] = !$result['opera'] && (str_contains($ua, 'msie 9'));
		$result['ie8'] = !$result['opera'] && (str_contains($ua, 'msie 8'));
		$result['ie7'] = !$result['opera'] && (str_contains($ua, 'msie 7')) && !$result['ie8'];
		$result['ie6'] = !$result['opera'] && (str_contains($ua, 'msie 6')) && !$result['ie7'] && !$result['ie8'];
		$result['ie'] = !$result['opera'] && (str_contains($ua, 'msie'));

		$result['kindle'] = (str_contains($ua, 'kindle'));
		$result['surface'] = (str_contains($ua, 'surface'));

		$result['firefox'] = (str_contains($ua, 'firefox'));
		$result['safari'] = (str_contains($ua, 'safari')) && !$result['chrome'];
		$result['mac'] = (str_contains($ua, 'macintosh'));

		$result['linux'] = (str_contains($ua, 'linux'));
		$result['windows'] = (str_contains($ua, 'windows'));
		$result['macIntel'] = $result['mac'] && (str_contains($ua, 'intel'));
		$result['macPPC'] = $result['mac'] && (!str_contains($ua, 'intel'));

		$result['mobile'] = $result['ios'] || (str_contains($ua, 'mobile'));
		$result['phone'] = $result['iphone']; // TODO This seems wrong - android phones?
		$result['tablet'] = !$result['phone'] && ($result['ipad'] || $result['kindle'] || $result['surface']);
		$result['desktop'] = !$result['phone'] && !$result['tablet'];

		return $result;
	}

	/**
	 * Classify user agent
	 *
	 * @return array
	 */
	private function _classify(): array
	{
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
