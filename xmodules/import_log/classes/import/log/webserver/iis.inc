<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/import_log/classes/import/log/webserver/iis.inc $
 * @package ruler
 * @subpackage import
 * @author kent
 * @copyright Copyright &copy; 2009, Market Ruler, LLC
 * Created on Wed Feb 24 13:48:13 EST 2010 13:48:13
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Import_Log_WebServer_IIS extends Import_Log_WebServer {
	protected $header_read_size = 0;
	protected $field_map = array();
	static $field_to_internal = array(
		"c-ip" => self::IP,
		"date" => self::Date,
		"time" => self::Time,
		"cs-username" => self::User,
		"s-sitename" => "IIS-Site_Name",
		"s-computername" => self::Server_Name,
		"s-ip" => self::Server_IP,
		"s-port" => self::Server_Port,
		"cs-method" => self::Request_Method,
		"cs-uri-stem" => self::Request_URI,
		"cs-uri-query" => self::Request_Query_String,
		"sc-status" => self::Response_Status,
		"sc-substatus" => "IIS-Substatus",
		"sc-win32-status" => "IIS-Win32-Status",
		"sc-bytes" => self::Response_Size,
		"cs-bytes" => self::Request_Size,
		"time-taken" => self::Duration,
		"cs-version" => self::Request_Version,
		"cs-host" => self::Request_Host,
		"cs(User-Agent)" => self::Request_User_Agent,
		"cs(Cookie)" => self::Request_Cookie,
		"cs(Referer)" => self::Request_Referrer
	);
	static $unescape_values = array(
		self::Request_User_Agent,
		self::Request_Cookie,
		self::Request_URI,
		self::Request_Query_String
	);
	function __construct($filename, $options = null) {
		parent::__construct($filename, $options);
		$this->field_map = explode(" ", 'date time s-sitename s-ip cs-method cs-uri-stem cs-uri-query s-port cs-username c-ip cs(User-Agent) sc-status sc-substatus sc-win32-status');
	}
	protected function is_state_line($line) {
		return strlen($line) > 0 && $line[0] === "#";
	}
	protected function read_state_line($line) {
		if ($line[0] !== "#") {
			return false;
		}
		$line = ltrim($line, "#");
		list($name, $value) = pair($line, ":", $line, null);
		if ($value !== null) {
			switch (strtolower($name)) {
				case "fields":
					$this->field_map = explode(" ", trim($value));
					Debug::dump($this->field_map);
					break;
				default :
					$this->set_option($name, $value);
					break;
			}
		}
		return true;
	}
	protected function _read_header($header_data) {
		$lines = explode("\n", $header_data);
		$bytes = 0;
		foreach ($lines as $line) {
			$line_bytes = strlen($line) + 1; // include \n
			$line = trim($line);
			if (self::is_state_line($line)) {
				self::read_state_line($line);
				$bytes += $line_bytes;
			} else {
				break;
			}
		}
		return $bytes;
	}
	public function read_line() {
		while (($line = $this->_read_line()) !== null) {
			if (self::is_state_line($line)) {
				self::read_state_line($line);
			} else {
				break;
			}
		}
		$parts = explode(" ", $line);
		$result = array();
		foreach ($this->field_map as $i => $field) {
			$result[avalue(self::$field_to_internal, $field, $field)] = avalue($parts, $i);
		}
		foreach (self::$unescape_values as $k) {
			if (array_key_exists($k, $result)) {
				$result[$k] = urldecode($result[$k]);
			}
		}
		if (arr::has($result, self::Request_URI, self::Request_Query_String)) {
			$qs = $result[self::Request_Query_String];
			if (!empty($qs)) {
				$result[self::Request_URI] .= "?$qs";
			}
		}
		return $this->derive_fields($result);
	}
}


/*

#Software: Microsoft Internet Information Services 6.0
#Version: 1.0
#Date: 2010-02-10 00:00:00
#Fields: date time s-sitename s-ip cs-method cs-uri-stem cs-uri-query s-port cs-username c-ip cs(User-Agent) sc-status sc-substatus sc-win32-status
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /Store/Style+Library/Images/bbbseal1.gif - 80 - 207.114.163.82 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+5.1;+Trident/4.0;+InfoPath.1;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.4506.2152;+.NET+CLR+3.5.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /PublishingImages/diag_pattern.gif - 80 - 207.114.163.82 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+5.1;+Trident/4.0;+InfoPath.1;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.4506.2152;+.NET+CLR+3.5.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /HRC/PublishingImages/footer_logo.png - 80 - 207.114.163.82 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+5.1;+Trident/4.0;+InfoPath.1;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.4506.2152;+.NET+CLR+3.5.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /_cbc/Store/Presentation/Template/js/tabcontent.js - 443 - 72.11.241.35 Mozilla/4.0+(compatible;+MSIE+7.0;+Windows+NT+5.1;+.NET+CLR+1.1.4322;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.04506.30) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /Store/Style+Library/Images/bttn_go.gif - 443 - 72.11.241.35 Mozilla/4.0+(compatible;+MSIE+7.0;+Windows+NT+5.1;+.NET+CLR+1.1.4322;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.04506.30) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.153 GET /HRC/Style+Library/Images/orange_carat.gif - 80 - 207.114.163.82 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+5.1;+Trident/4.0;+InfoPath.1;+.NET+CLR+2.0.50727;+.NET+CLR+3.0.4506.2152;+.NET+CLR+3.5.30729) 200 0 0

...

#Software: Microsoft Internet Information Services 6.0
#Version: 1.0
#Date: 2010-02-10 00:00:00
#Fields: date time s-sitename s-ip cs-method cs-uri-stem cs-uri-query s-port cs-username c-ip cs(User-Agent) sc-status sc-substatus sc-win32-status
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /Store/Style+Library/Images/border_right.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /Store/Style+Library/Images/box.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /Store/Style+Library/Images/border_left.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /Store/Style+Library/Images/nav_arrow.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /store/img/misc/transparent.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0
2010-02-09 23:59:59 W3SVC78508868 65.160.233.154 GET /Store/Style+Library/Images/icon_magnify.gif - 80 - 71.160.209.89 Mozilla/4.0+(compatible;+MSIE+8.0;+Windows+NT+6.0;+Trident/4.0;+SLCC1;+.NET+CLR+2.0.50727;+.NET+CLR+3.5.30729;+.NET+CLR+3.0.30729) 200 0 0

*/
