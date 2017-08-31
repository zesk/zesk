<?php

/**
 *
 */
namespace zesk;

/**
 * Module object for Critical alerts
 *
 * @author kent
 *
 */
class Module_Critical extends Module {
	/**
	 *
	 * @var string
	 */
	const setting_critical_alerts = "critical_alerts";
	
	/**
	 *
	 * @var string
	 */
	const setting_email = "email";
	
	/**
	 *
	 * @var integer
	 */
	const lock_timeout = 10;
	
	/**
	 *
	 * @var array
	 */
	protected $emails = array();
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Module::initialize()
	 */
	public function initialize() {
		$emails = $this->option_list(self::setting_email, array(), ",");
		$bad_emails = array();
		foreach ($emails as $email) {
			$email = trim($email);
			if (is_email($email)) {
				$this->emails[] = $email;
			} else {
				$bad_emails[] = $email;
			}
		}
		$global_name = __CLASS__ . "::" . self::setting_email;
		if (count($this->emails) === 0) {
			throw new Exception_Configuration($global_name, "No emails configured in $global_name");
		}
		if (count($bad_emails) > 0) {
			$this->_alert("$global_name invalid email address: " . implode(",", $bad_emails));
		}
	}
	
	/**
	 * Run every minute to check if alerts should be sent
	 */
	public function hook_cron_cluster() {
		$this->send_critical_alerts();
	}
	
	/**
	 * Load the alerts from the database
	 *
	 * @return array
	 */
	private function _fetch_alerts() {
		$settings = Settings::instance();
		return to_array($settings->get(self::setting_critical_alerts, array()));
	}
	
	/**
	 * Store the alerts in the database
	 *
	 * @param array $alerts
	 */
	private function _store_alerts(array $alerts) {
		$settings = Settings::instance();
		$settings->set(self::setting_critical_alerts, $alerts)->flush();
	}
	
	/**
	 * Log a single alert
	 *
	 * @param string $sms_message
	 * @param integer $frequency
	 * @return Module_Critical|null
	 */
	public function alert($sms_message, $frequency = 3600) {
		if (!is_numeric($frequency)) {
			throw new Exception_Parameter("Parameter 2 to {method} should be integer value {value} is of type {type}", array(
				"method" => __METHOD__,
				"value" => to_text($frequency),
				"type" => type($frequency)
			));
		}
		$map = array(
			"when" => date('Y-m-d H:i:s')
		);
		try {
			$map["server"] = Server::singleton($this->application)->name;
		} catch (Exception $e) {
		}
		$lock = Lock::instance($this->application, __METHOD__);
		if ($lock->acquire(self::lock_timeout) === null) {
			$this->application->logger->error("Unable to lock {method}: Message not sent {sms_message}", array(
				"sms_message" => $sms_message,
				"method" => __METHOD__
			));
			return null;
		}
		$alerts = $this->_fetch_alerts();
		
		$alert_id = md5($sms_message);
		$alert = avalue($alerts, $alert_id, array());
		$alert['frequency'] = min(avalue($alert, 'frequency', $frequency), $frequency);
		$alert['first'] = avalue($alert, 'first', time());
		$alert['count'] = $map['count'] = avalue($alert, 'count', 0) + 1;
		$alert['recent'] = time();
		$alert['message'] = map($sms_message, $map);
		$alerts[$alert_id] = $alert;
		
		$this->_store_alerts($alerts);
		
		$lock->release();
		
		if ($frequency === 0) {
			$this->send_critical_alerts();
		}
		return $this;
	}
	
	/**
	 * Send out all alerts and update alert state after sending
	 *
	 * @return NULL
	 */
	public function send_critical_alerts() {
		$logger = $this->application->logger;
		
		$lock = Lock::instance($this->application, __METHOD__);
		if ($lock->acquire(self::lock_timeout) === null) {
			$logger->error("Unable to lock {method}: can not send alerts", array(
				"method" => __METHOD__
			));
			return null;
		}
		$alerts = $this->_fetch_alerts();
		$sends = array();
		$now = time();
		foreach ($alerts as $alert_id => $alert) {
			$first = $frequency = $count = $message = null;
			extract($alert, EXTR_IF_EXISTS);
			if (!is_numeric($first) || !is_numeric($frequency)) {
				$logger->error("Alert is improperly formatted:\nPHP: {raw}\nJSON: {json}", array(
					"raw" => serialize($alert),
					"json" => json_encode($alert)
				));
				unset($alerts[$alert_id]);
				continue;
			}
			if ($now > $first + $frequency) {
				$sends[] = $message . ($count > 1 ? " (${count}x)" : "");
				$logger->error("Sending critical alert {message} {alert}", array(
					"message" => $message,
					"alert" => $alert
				));
				unset($alerts[$alert_id]);
			} else {
				$unit = "second";
				$remain = $first + $frequency - $now;
				if ($remain > 120) {
					$unit = "minute";
					$remain = round($remain / 60);
					if ($remain > 120) {
						$unit = "hour";
						$remain = round($remain / 60);
					}
				}
				$logger->notice("Will send alert {message} in {remain} {units}", array(
					"message" => $message,
					"remain" => $remain,
					"units" => Locale::plural($unit, $remain)
				));
			}
		}
		if (count($sends) > 0) {
			$this->_store_alerts($alerts);
			$emails = $this->option_list('email', array(), ",");
			foreach ($emails as $email) {
				Mail::sendmail($email, $this->option("from"), $this->option("subject"), implode("\n", $sends), false, false, false, array(
					'no_force_to' => true
				));
			}
		}
		$lock->release();
	}
}
