<?php
/**
 * 
 */
namespace zesk;

/**
 *
 * @author kent
 * @see Class_Forgot
 * @property id $id
 * @property string $login
 * @property User $user
 * @property Session_Database $session
 * @property string $new_password
 * @property hex $code
 * @property Timestamp $created
 * @property Timestamp $updated
 * @see Schema_Forgot
 */
class Forgot extends Object {
	/**
	 * List of variables in email
	 *
	 * @var array
	 */
	private static $mappable_variables = array(
		"subject"
	);
	/**
	 *
	 * @param Application $application        	
	 */
	public static function cron_cluster_minute(Application $application) {
		$expire_seconds = -abs(to_integer($application->configuration->path_get("Module_Forgot::expire_seconds"), 3600));
		$query = $application->query_delete(__CLASS__)->where("Created|<=", Timestamp::now()->add_unit($expire_seconds, Timestamp::UNIT_SECOND));
		$query->execute();
		$affected_rows = $query->affected_rows();
		if ($affected_rows > 0) {
			$application->logger->notice("Forgot::cron_minute deleted {affected_rows} forgotten rows", compact("affected_rows"));
		}
	}
	
	/**
	 *
	 * @param Request $request        	
	 * @return Mail
	 */
	public function notify(Request $request) {
		$user = $this->user;
		$variables = array(
			"subject" => __('Forgotten password request for {user_email}'),
			"user_login" => $user->login(),
			"user_email" => $user->email(),
			"user" => $user,
			"forgot" => $this
		);
		
		$variables += arr::kprefix($this->members(), "forgot_");
		$variables += arr::kprefix($user->members(), "user_");
		$variables += arr::kprefix($request->variables(), "request_");
		$variables += arr::kprefix($request->url_parts(), "url_");
		
		$variables = $this->call_hook_arguments("notify_variables", array(
			$variables
		), $variables);
		
		$variables = arr::kunprefix($this->options, "notify_", true) + $variables;
		
		/*
		 * Map subject again 
		 */
		foreach (self::$mappable_variables as $key) {
			$variables[$key] = map($variables[$key], $variables);
		}
		
		$mail = $this->call_hook("notify", $variables);
		if ($mail instanceof Mail) {
			return $mail;
		}
		$mail_options = Mail::load_theme($this->application, "object/zesk/forgot/notify", $variables);
		return Mail::mulitpart_send($mail_options);
	}
	
	/**
	 *
	 * @return Forgot
	 */
	public function validated() {
		$user = $this->user;
		$user->password($this->new_password)->store();
		$this->updated = "now";
		$this->store();
		$this->call_hook("validated");
		$query = $this->query_update();
		$query->value("*updated", $query->sql()
			->now())
			->where(array(
			"user" => $user,
			"updated" => null
		));
		$query->exec();
		return $this;
	}
}
