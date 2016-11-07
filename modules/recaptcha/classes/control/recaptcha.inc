<?php
/**
 * 
 */
use zesk\JSON;
use zesk\HTML;

/**
 * 
 * @author kent
 *
 */
class Control_reCAPTCHA extends Control {
	/**
	 * 
	 * @var string
	 */
	static $url = "https://www.google.com/recaptcha/api/siteverify";
	
	/**
	 * 
	 * @var array
	 */
	static $error_codes = array(
		"missing-input-secret" => "The secret parameter is missing.",
		"invalid-input-secret" => "The secret parameter is invalid or malformed.",
		"missing-input-response" => "The response parameter is missing.",
		"invalid-input-response" => "The response parameter is invalid or malformed."
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->set_option("verified_name", $this->name() . "-verified");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::is_visible()
	 */
	function is_visible() {
		return !to_bool(avalue($this->theme_variables, 'verified'));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::validate()
	 */
	public function validate() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		$this->theme_variables['reverify'] = false;
		if (($verified = $this->is_verified()) === true) {
			return true;
		}
		if ($verified === null) {
			$this->theme_variables['reverify'] = true;
			return false;
		}
		$http = new Net_HTTP_Client(self::$url);
		$http->method_post(true);
		$ip = $this->request->ip();
		$http->data(array(
			"secret" => $this->option("secret_key"),
			"response" => $this->request->get("g-recaptcha-response"),
			"remoteip" => $ip
		));
		$result = $http->go();
		if ($this->option_bool('debug')) {
			$debug .= serialize($http);
			$debug .= "Result: $result\n";
			$debug .= "Response: " . $http->response_code_type() . "\n";
			$debug .= "Data: " . _dump(JSON::decode($result)) . "\n";
			file_put_contents($this->application->application_root('recaptcha.log'), $debug);
		}
		$code_type = $http->response_code_type();
		if ($code_type === Net_HTTP::response_type_success) {
			try {
				$data = JSON::decode($result);
				$success = to_bool(avalue($data, 'success'));
				$errors = array();
				$error_codes = avalue($data, 'error-codes', array());
				foreach ($error_codes as $error_code) {
					if (array_key_exists($error_code, self::$error_codes)) {
						$errors[] = __(self::$error_codes[$error_code]);
					}
				}
				if (count($errors) > 0) {
					$this->error(implode("\n", $errors));
				} else if ($success === true) {
					$this->verified();
					return true;
				} else {
					$this->error(__("Please verify that you are not a robot."));
				}
			} catch (Exception_Parse $e) {
				$this->error(__("Unable to verify that you are not a robot, problem decoding. Please try again later."));
			}
		} else {
			$this->error(__("Unable to verify that you are not a robot. Please try again later."));
			Module_Critical::alert("reCAPTCHA response type $code_type from $ip");
		}
		return false;
	}
	
	/**
	 * 
	 */
	private function verified() {
		$now = time();
		$name = $this->name();
		$verified_html = "";
		$verified_html .= HTML::input_hidden("$name-verified-key", md5($now . "|" . $this->option("secret_key")));
		$verified_html .= HTML::input_hidden("$name-verified-timestamp", $now);
		
		$this->theme_variables['verified_html'] = $verified_html;
		$this->theme_variables['verified'] = true;
	}
	/**
	 * 
	 * @return boolean|NULL
	 */
	private function is_verified() {
		$name = $this->name();
		$now = time();
		$key = $this->request->get("$name-verified-key");
		if (!$key) {
			return false;
		}
		$time = $this->request->get("$name-verified-timestamp");
		if (!is_numeric($time)) {
			return null;
		}
		$time = doubleval($time);
		$delta = $now - $time;
		if ($delta < 0) {
			log::error("Time submitted was before now? {now} {time} {key} Bad server time?", compact("now", "time", "key"));
			return null;
		}
		if ($delta < $this->option_integer("verification_minimum_age", 3)) {
			$this->error(__("You submitted the form too quickly, please verify you are a not a robot again."));
			return null;
		}
		if ($delta > $this->option_integer("verification_seconds_to_live", 600)) {
			$this->error(__("Your verification has expired, please verify you are not a robot again."));
			return null;
		}
		$check = md5($time . "|" . $this->option("secret_key"));
		if ($check === $key) {
			$this->verified();
			return true;
		}
		$this->error(__("The checksum did not match. Please verify that you are not a robot one more time."));
		return null;
	}
}
