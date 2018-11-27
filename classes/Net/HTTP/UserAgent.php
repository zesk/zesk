<?php
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
    private static $classifications_names = array(
        'platform' => 'Platform',
        'browser' => 'Web Browser',
        'mobile' => 'Mobile/Desktop',
        'user_agent' => 'User Agent',
    );

    /**
     * Presence of these true values in ->is will display classification
     * Last array item in each is the default
     *
     * @var array
     */
    private static $classifications = array(
        'platform' => array(
            'mac' => 'mac',
            'windows' => "windows",
            'linux' => "linux",
            'iphone' => 'iphone',
            'ipad' => 'ipad',
            'android' => 'android',
            '' => 'unknown',
        ),
        'browser' => array(
            'safari' => 'safari',
            'chrome' => 'chrome',
            'ie' => 'ie',
            'firefox' => 'firefox',
            '' => 'unknown',
        ),
        'mobile' => array(
            'mobile' => 'mobile',
            '' => 'desktop',
        ),
    );

    /**
     *
     * @var array
     */
    private static $lang_classifications = array(
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
    );

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
    public function __wakeup() {
        $this->is = null;
        $this->classify = null;
    }

    /**
     *
     * @return string[]
     */
    public function __sleep() {
        return array(
            "user_agent",
        );
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

        $result['opera'] = (strpos($ua, "opera") !== false);

        $result['iphone'] = (strpos($ua, "iphone") !== false);
        $result['ipad'] = (strpos($ua, "ipad") !== false);
        $result['ios'] = $result['iphone'] || $result['ipad'] || (strpos($ua, "ios") !== false);
        foreach (to_list("5;6;7;8;9;10") as $v) {
            $result["ios${v}"] = $result['ios'] && (strpos($ua, "os ${v}_") !== false);
        }
        $result['webkit'] = strpos($ua, "applewebkit") !== false;
        $result['chrome'] = strpos($ua, "chrome/") !== false;

        $result['ie10'] = !$result['opera'] && (strpos($ua, "msie 10") !== false);
        $result['ie9'] = !$result['opera'] && (strpos($ua, "msie 9") !== false);
        $result['ie8'] = !$result['opera'] && (strpos($ua, "msie 8") !== false);
        $result['ie7'] = !$result['opera'] && (strpos($ua, "msie 7") !== false) && !$result['ie8'];
        $result['ie6'] = !$result['opera'] && (strpos($ua, "msie 6") !== false) && !$result['ie7'] && !$result['ie8'];
        $result['ie'] = !$result['opera'] && (strpos($ua, "msie") !== false);

        $result['kindle'] = (strpos($ua, "kindle") !== false);
        $result['surface'] = (strpos($ua, "surface") !== false);

        $result['firefox'] = (strpos($ua, "firefox") !== false);
        $result['safari'] = (strpos($ua, "safari") !== false) && !$result['chrome'];
        $result['mac'] = (strpos($ua, "macintosh") !== false);

        $result['linux'] = (strpos($ua, "linux") !== false);
        $result['windows'] = (strpos($ua, "windows") !== false);
        $result['mac_intel'] = $result['mac'] && (strpos($ua, "intel") !== false);
        $result['mac_ppc'] = $result['mac'] && (strpos($ua, "intel") === false);

        $result['mobile'] = $result['ios'] || (strpos($ua, "mobile") !== false);
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
        $result = array();
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
