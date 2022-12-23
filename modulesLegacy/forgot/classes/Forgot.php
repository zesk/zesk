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
class Forgot extends ORMBase {
	/**
	 * List of variables in email
	 *
	 * @var array
	 */
	private static $mappable_variables = [
		'subject',
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
			'subject' => $locale->__('Forgotten password request for {user_email}'),
			'user_login' => $user->login(),
			'user_email' => $user->email(),
			'user' => $user,
			'forgot' => $this,
		];

		$variables += ArrayTools::prefixKeys($this->members(), 'forgot_');
		$variables += ArrayTools::prefixKeys($user->members(), 'user_');
		$variables += ArrayTools::prefixKeys($request->variables(), 'request_');
		$variables += ArrayTools::prefixKeys($request->urlComponents(), 'url_');

		$variables = $this->callHookArguments('notify_variables', [
			$variables,
		], $variables);

		$variables = ArrayTools::keysRemovePrefix($this->options, 'notify_', true) + $variables;

		/*
		 * Map subject again
		 */
		foreach (self::$mappable_variables as $key) {
			$variables[$key] = map($variables[$key], $variables);
		}

		$mail = $this->callHook('notify', $variables);
		if ($mail instanceof Mail) {
			return $mail;
		}
		$mail_options = Mail::loadTheme($this->application, 'object/zesk/forgot/notify', $variables);
		return Mail::multipartFactory($this->application, $mail_options);
	}

	/**
	 *
	 * @return Forgot
	 */
	public function validated($plaintext_password) {
		if (empty($plaintext_password)) {
			throw new Exception_Parameter('{method} requires a non-empty new password', [
				'method' => __METHOD__,
			]);
		}
		$user = $this->user;
		$user->password($plaintext_password, true)->store();
		$this->updated = 'now';
		$this->store();
		$this->callHook('validated');
		$query = $this->queryUpdate();
		$query->value('*updated', $query->sql()
			->now())
			->where([
				'user' => $user,
				'updated' => null,
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
		return $this->created->duplicate()->addUnit($this->expire_seconds());
	}

	/**
	 *
	 * @param Timestamp $older
	 * @return integer
	 */
	public function delete_older(Timestamp $older) {
		return $this->query_delete()
			->addWhere('Created|<=', $older)
			->execute()
			->affectedRows();
	}
}
