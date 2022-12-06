<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use aws\classes\Module;

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
	public const setting_critical_alerts = 'critical_alerts';

	/**
	 *
	 * @var string
	 */
	public const setting_email = 'email';

	/**
	 *
	 * @var integer
	 */
	public const lock_timeout = 10;

	/**
	 *
	 * @var array
	 */
	protected $emails = [];

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Module::initialize()
	 */
	/**
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Parameter
	 */
	public function initialize(): void {
		$emails = $this->optionIterable(self::setting_email, [], ',');
		$bad_emails = [];
		foreach ($emails as $email) {
			$email = trim($email);
			if (is_email($email)) {
				$this->emails[] = $email;
			} else {
				$bad_emails[] = $email;
			}
		}
		$global_name = __CLASS__ . '::' . self::setting_email;
		if (count($this->emails) === 0) {
			throw new Exception_Configuration($global_name, 'No emails configured in {name}', ['name' => $global_name]);
		}
		if (count($bad_emails) > 0) {
			$this->alert("$global_name invalid email address: " . implode(',', $bad_emails));
		}
	}

	/**
	 * Run every minute to check if alerts should be sent
	 */
	public function hook_cron_cluster(): void {
		$this->send_critical_alerts();
	}

	/**
	 * Load the alerts from the database
	 *
	 * @return array
	 */
	private function _fetch_alerts() {
		$settings = Settings::singleton($this->application);
		return toArray($settings->get(self::setting_critical_alerts, []));
	}

	/**
	 * Store the alerts in the database
	 *
	 * @param array $alerts
	 */
	private function _store_alerts(array $alerts): void {
		$settings = Settings::singleton($this->application);
		$settings->set(self::setting_critical_alerts, $alerts)->flush();
	}

	/**
	 * Log a single alert
	 *
	 * @param string $sms_message
	 * @param int $frequency
	 * @return Module_Critical|null
	 */
	public function alert(string $sms_message, int $frequency = 3600) {
		$map = [
			'when' => date('Y-m-d H:i:s'),
		];

		$map['server'] = Server::singleton($this->application)->name;
		$lock = Lock::instance($this->application, __METHOD__);
		if ($lock->acquire(self::lock_timeout) === null) {
			$this->application->logger->error('Unable to lock {method}: Message not sent {sms_message}', [
				'sms_message' => $sms_message,
				'method' => __METHOD__,
			]);
			return null;
		}
		$alerts = $this->_fetch_alerts();

		$alert_id = md5($sms_message);
		$alert = $alerts[$alert_id] ?? [];
		$alert['frequency'] = min($alert['frequency'] ?? $frequency, $frequency);
		$alert['first'] ??= time();
		$alert['count'] = $map['count'] = ($alert['count'] ?? 0) + 1;
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
			$logger->error('Unable to lock {method}: can not send alerts', [
				'method' => __METHOD__,
			]);
			return null;
		}
		$alerts = $this->_fetch_alerts();
		$sends = [];
		$now = time();
		foreach ($alerts as $alert_id => $alert) {
			$first = $frequency = $count = $message = null;
			extract($alert, EXTR_IF_EXISTS);
			if (!is_numeric($first) || !is_numeric($frequency)) {
				$logger->error("Alert is improperly formatted:\nPHP: {raw}\nJSON: {json}", [
					'raw' => serialize($alert),
					'json' => json_encode($alert),
				]);
				unset($alerts[$alert_id]);

				continue;
			}
			if ($now > $first + $frequency) {
				$sends[] = $message . ($count > 1 ? " (${count}x)" : '');
				$logger->error('Sending critical alert {message} {alert}', [
					'message' => $message,
					'alert' => $alert,
				]);
				unset($alerts[$alert_id]);
			} else {
				$unit = 'second';
				$remain = $first + $frequency - $now;
				if ($remain > 120) {
					$unit = 'minute';
					$remain = round($remain / 60);
					if ($remain > 120) {
						$unit = 'hour';
						$remain = round($remain / 60);
					}
				}
				$logger->notice('Will send alert {message} in {remain} {units}', [
					'message' => $message,
					'remain' => $remain,
					'units' => $this->application->locale->plural($unit, $remain),
				]);
			}
		}
		if (count($sends) > 0) {
			$this->_store_alerts($alerts);
			$emails = $this->optionIterable('email', [], ',');
			foreach ($emails as $email) {
				Mail::sendmail($this->application, $email, $this->option('from'), $this->option('subject'), implode("\n", $sends), false, false, false, [
					'no_force_to' => true,
				]);
			}
		}
		$lock->release();
	}
}
