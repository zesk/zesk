<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/Client.php $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

abstract class Net_Client extends Hookable {
	/**
	 * Connection string
	 * @var string
	 */
	protected $url;
	/**
	 * Parsed URL.
	 * Valid if url is a valid url.
	 * @var array
	 */
	protected $url_parts;
	
	/**
	 * Error log
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * Create a Net_Client
	 * @param string $url
	 * @param array $options
	 * @return Net_Client
	 */
	public static function factory(Application $application, $url, array $options = array()) {
		$scheme = strtolower(URL::scheme($url));
		$scheme = avalue(array(
			"https" => "http"
		), $scheme, $scheme);
		try {
			$class = "Net_" . strtoupper($scheme) . "_Client";
			$object = new $class($application, $url, $options);
			return $object;
		} catch (Exception_Class_NotFound $e) {
			$application->hooks->call("exception", $e);
			return null;
		}
	}
	/**
	 * Create a new Net_Client object
	 * @param string $url smtp://user:pass@server:port/
	 * @param array $options Options which change the behavior of this SMTP_Client connection
	 * @return SMTP_Client
	 */
	public function __construct(Application $application, $url, array $options = array()) {
		parent::__construct($application, $options);
		$this->url = $url;
		$this->url_parts = URL::parse($url);
		$this->inherit_global_options();
	}
	public function __toString() {
		return $this->url;
	}
	
	/**
	 *
	 * @return Application
	 */
	final public function application() {
		return $this->application;
	}
	/**
	 * Connect
	 * @return boolean
	 */
	abstract public function connect();
	/**
	 * Disconnect
	 * @return boolean true if disconnected, false if already disconnected
	 */
	abstract public function disconnect();
	/**
	 * Are we connected?
	 * @return false;
	 */
	abstract public function is_connected();
	
	/**
	 * Force connection
	 */
	protected function require_connect() {
		if (!$this->is_connected()) {
			$this->connect();
		}
	}
	/**
	 * Retrieve the URL or URL component from this Net_Client
	 * @param string $component
	 * @return string
	 */
	public function url($component = null) {
		if ($component !== null) {
			return avalue($this->url_parts, $component, null);
		}
		return $this->url;
	}
	/**
	 * Log a message
	 * @param string $message
	 */
	protected function log($message) {
		if ($this->option_bool('debug')) {
			$this->application->logger->debug($message);
		}
	}
	
	/**
	 * Parse a UNIX-ish LS line
	 * @param string $line
	 * @throws Exception_Syntax
	 * @return NULL array
	 */
	protected function parse_ls_line($line) {
		$line = trim($line);
		
		$fields = preg_split('/\s+/', $line, 9);
		if (strtolower($fields[0]) === "total") {
			return null;
		}
		if (count($fields) !== 9) {
			throw new Exception_Syntax("Improper listing line: $line");
		}
		$entry = ArrayTools::map_keys($fields, array(
			0 => 'mode',
			1 => 'links',
			2 => 'owner',
			3 => 'group',
			4 => 'size',
			5 => 'month',
			6 => 'day',
			7 => 'timeyear',
			8 => 'name'
		));
		$name = $entry['name'];
		if (empty($name) || ($name == ".") || ($name == "..")) {
			return null;
		}
		$entry['name'] = $name = trim(StringTools::left($name, "->", $name));
		$entry['type'] = File::ls_type($entry['mode']);
		return $this->_parse_date($entry);
	}
	
	/**
	 *
	 * @param unknown_type $month
	 * @param unknown_type $day
	 * @param unknown_type $timeyear
	 * @return NULL boolean Timestamp
	 */
	private function _parse_date(array $entry) {
		$mm = array(
			"jan",
			"feb",
			"mar",
			"apr",
			"may",
			"jun",
			"jul",
			"aug",
			"sep",
			"oct",
			"nov",
			"dec"
		);
		$month = $day = $timeyear = null;
		extract($entry, EXTR_IF_EXISTS);
		$month = array_search(strtolower(substr($month, 0, 3)), $mm, true);
		if ($month === false) {
			return null;
		}
		if (!is_numeric($day)) {
			$this->log("FTP_Client::_parse_date: Day not numeric: $day");
			return null;
		}
		if (strstr($timeyear, ":") === false) {
			$date = new Timestamp();
			$date->ymdhms($timeyear, $month, $day, 0, 0, 0);
			$entry['mtime_granularity'] = 'day';
		} else {
			list($hour, $minute) = explode(":", $timeyear);
			if (!is_numeric($hour) || !is_numeric($minute)) {
				return null;
			}
			$date = new Timestamp();
			$date->ymdhms($date->year(), $month, $day, $hour, $minute, 0);
			$entry['mtime_granularity'] = 'minute';
		}
		return array(
			'mtime' => $date
		) + $entry;
	}
}

