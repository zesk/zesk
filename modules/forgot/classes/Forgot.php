<?php declare(strict_types=1);
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
 * @property Session_ORM $session
 * @property hex $code
 * @property Timestamp $created
 * @property Timestamp $updated
 * @see Schema_Forgot
 */
class Forgot extends ORM {
	/**
	 * List of variables in email
	 *
	 * @var array
	 */
	private static $mappable_variables = [
		"subject",
	];

	/**
	 *
	 * @param Request $request
	 * @return Mail
	 */
	public function notify(Request $request) {
		$user = $this->user;
		$locale = $this->application->locale;
		$variables = [
			"subject" => $locale->__('Forgotten password request for {user_email}'),
			"user_login" => $user->login(),
			"user_email" => $user->email(),
			"user" => $user,
			"forgot" => $this,
		];

		$variables += ArrayTools::kprefix($this->members(), "forgot_");
		$variables += ArrayTools::kprefix($user->members(), "user_");
		$variables += ArrayTools::kprefix($request->variables(), "request_");
		$variables += ArrayTools::kprefix($request->url_variables(), "url_");

		$variables = $this->call_hook_arguments("notify_variables", [
			$variables,
		], $variables);

		$variables = ArrayTools::kunprefix($this->options, "notify_", true) + $variables;

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
		return Mail::multipart_send($this->application, $mail_options);
	}

	/**
	 *
	 * @return Forgot
	 */
	public function validated($plaintext_password) {
		if (empty($plaintext_password)) {
			throw new Exception_Parameter("{method} requires a non-empty new password", [
				"method" => __METHOD__,
			]);
		}
		$user = $this->user;
		$user->password($plaintext_password, true)->store();
		$this->updated = "now";
		$this->store();
		$this->call_hook("validated");
		$query = $this->query_update();
		$query->value("*updated", $query->sql()
			->now())
			->where([
			"user" => $user,
			"updated" => null,
		]);
		$query->execute();
		return $this;
	}

	/**
	 * Validate the token string
	 *
	 * @param string $token
	 * @return boolean
	 */
	public static function valid_token($token) {
		return preg_match('/[0-9a-f]{32}/i', strval($token)) !== 0;
	}

	/**
	 * Number of seconds after which this object is considered no longer valid.
	 *
	 * @return integer
	 */
	public function expire_seconds() {
		return $this->application->forgot_module()->request_expire_seconds();
	}

	/**
	 * Has this expired?
	 *
	 * @return boolean
	 */
	public function expired() {
		return $this->expiration()->beforeNow(true);
	}

	/**
	 * Fetch the expiration date
	 *
	 * @return \zesk\Timestamp
	 */
	public function expiration() {
		return $this->created->duplicate()->add_unit($this->expire_seconds());
	}

	/**
	 *
	 * @param Timestamp $older
	 * @return integer
	 */
	public function delete_older(Timestamp $older) {
		return $this->query_delete()
			->where("Created|<=", $older)
			->execute()
			->affected_rows();
	}
}
