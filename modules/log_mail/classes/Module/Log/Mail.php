<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Log_Mail
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Log_Mail extends Module {
	/**
	 *
	 * @param Application $application
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->hooks->add(Mail::class . '::send', [
			$this,
			"hook_mail_send",
		]);
	}

	/**
	 *
	 * @param Application $application
	 */
	public function hook_cron_cluster_day(): void {
		$application = $this->application;
		$delete_after_days = to_integer($this->option('delete_after_days'), 0);
		if ($delete_after_days > 0) {
			$delete_before = Timestamp::now()->add_unit(-$delete_after_days, Timestamp::UNIT_DAY);
			/* @var $query zesk\Database_Query_Delete */
			$class = Log_Mail::class;
			$n_affected = $application->orm_registry($class)
				->query_delete()
				->where("created|<=", $delete_before)
				->exec()
				->affected_rows();
			if ($n_affected > 0) {
				$application->logger->notice("Deleted {n_affected} {class} rows older than {delete_before}", [
					"delete_before" => $delete_before,
					"n_affected" => $n_affected,
					"class" => $class,
				]);
				$object = $application->orm_registry($class);
				$table = $object->table();
				$object->database()->query("OPTIMIZE TABLE " . $table);
			}
		}
	}

	/**
	 * Log upon mail send
	 *
	 * @param Mail $mail
	 * @return boolean
	 */
	public function hook_mail_send(Mail $mail) {
		$app = $this->application;
		$request = $app->request();
		$session = $user = null;
		if ($request) {
			$session = $app->session($request, false);
			$user = $app->user($request, false);
			if ($user && !$user->authenticated($app->request())) {
				$user = null;
			}
		}
		$code = $mail->header(Mail::HEADER_MESSAGE_ID);
		if ($code === null) {
			$code = "";
		}
		$log_mail = $app->orm_factory(Log_Mail::class, [
			'code' => $code,
			'user' => $user,
			'session' => $session,
			'from' => $mail->header(Mail::HEADER_FROM),
			'to' => $mail->header(Mail::HEADER_TO),
			'subject' => $subject = $mail->header(Mail::HEADER_SUBJECT),
			'body' => $mail->body,
		]);
		$table_name = $log_mail->table();
		if (!$log_mail->database()->table_exists($table_name)) {
			$app->logger->warning("Can not log message with subject {subject} ... table does not exist", [
				"subject" => $subject,
			]);
			return true;
		}
		if ($mail->optionBool('no_force_to')) {
			return true;
		}
		$log_mail = $log_mail->register();
		if ($log_mail) {
			if ($log_mail->optionBool('no_send')) {
				// Prevent sending
				$mail->sent = time();
			} elseif ($log_mail->option('force_to')) {
				$to = $mail->header(Mail::HEADER_TO);
				if (!str_contains($to, "bounce-test")   && !str_contains($to, "bounce@")) {
					$mail->header(Mail::HEADER_TO, $log_mail->option('force_to'));
				}
			}
		}
		return true;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::model_classes()
	 */
	public function model_classes() {
		return [
			Log_Mail::class,
		];
	}
}
