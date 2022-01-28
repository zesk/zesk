<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Forgot
 * @author kent
 *
 */
class Controller_Forgot extends Controller_Theme {
	/**
	 * @return integer
	 */
	private function request_expire_seconds() {
		return $this->application->forgot_module()->request_expire_seconds();
	}

	/**
	 *
	 */
	public function action_index() {
		return $this->control($this->widget_factory(Control_Forgot::class));
	}

	/**
	 *
	 */
	public function action_unknown() {
		return $this->application->theme('forgot/unknown');
	}

	/**
	 *
	 */
	public function action_sent() {
		return $this->application->theme('forgot/sent');
	}

	/**
	 *
	 */
	public function action_complete($token) {
		return $this->application->theme('forgot/complete');
	}

	/**
	 * Fetch the forgotten password expiration value (seconds)
	 *
	 * @return \zesk\Controller
	 */
	public function action_expiration() {
		return $this->json([
			"expiration" => $this->request_expire_seconds(),
		]);
	}

	/**
	 *
	 * @param string $token
	 * @return Forgot
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	protected function find_forgot($token) {
		return $this->application->orm_factory(Forgot::class, [
			"code" => $token,
		])->find();
	}

	/**
	 *
	 * @param string $token
	 * @return Response As JSON
	 */
	public function action_status($token) {
		$json = [
			"status" => false,
			"token" => $token,
		];
		$locale = $this->application->locale;
		if (!Forgot::valid_token($token)) {
			$this->response->status(Net_HTTP::STATUS_BAD_REQUEST, "Invalid token");
			return $this->json($json + [
				"type" => "invalid-token",
				"message" => $locale->__("The forgotten password URL is an incorrect format."),
			]);
		}
		/* @var $forgot Forgot */
		$forgot = $this->find_forgot($token);
		if (!$forgot) {
			$this->response->status(Net_HTTP::STATUS_FILE_NOT_FOUND, "Token not found");
			return $this->json($json + [
				"type" => "token-not-found",
				"message" => $locale->__("Unable to find the forgotten password request."),
			]);
		}
		/* @var $user User */
		try {
			$user = $forgot->user;
		} catch (Exception_ORM_NotFound $nfe) {
			$user = null;
		}
		if (!$user instanceof User) {
			$this->response->status(Net_HTTP::STATUS_FILE_NOT_FOUND, "User not found");
			return $this->json($json + [
				"type" => "user-not-found",
				"message" => $locale->__("There was a problem with your user account - please try again."),
			]);
		}
		$session = $this->application->session($this->request);
		if ($forgot->id() !== $session->forgot) {
			$this->response->status(Net_HTTP::STATUS_EXPECTATION_FAILED, "Session mismatch");
			return $this->json($json + [
				"type" => "session-mismatch",
				"message" => $locale->__("You need to update your password using the same browser as the initial request."),
			]);
		}

		if (!$forgot->member_is_empty('updated')) {
			$this->response->status(Net_HTTP::STATUS_UNPROCESSABLE_ENTITY, "Already used");
			return $this->json($json + [
				"type" => "already",
				"message" => $locale->__("Forgotten password request was already used."),
			]);
		}

		$expiration = $forgot->expiration();
		return $this->json([
			"status" => true,
			"token" => $token,
			"expiration" => $expiration->unix_timestamp(),
			"expiration-string" => $expiration->__toString(),
			"timeout" => $this->request_expire_seconds(),
		]);
	}

	/**
	 *
	 * @param unknown $token
	 */
	public function action_validate($token) {
		$locale = $this->application->locale;
		$prefer_json = $this->request->prefer_json();
		if (!preg_match('/[0-9a-f]{32}/i', $token)) {
			$args = [
				"token" => $token,
				"message_type" => "invalid-token",
				"message" => $locale->__("The validation token passed in the URL is an incorrect format."),
			];
			return $this->application->theme('forgot/not-valid', $args);
		}
		/* @var $forgot Forgot */
		$forgot = $this->application->orm_factory(Forgot::class, [
			"code" => $token,
		])->find();
		if (!$forgot) {
			return $this->application->theme('forgot/not-found', [
				"token" => $token,
				"message_type" => "not-found",
				"message" => $locale->__("Unable to find the validation token."),
			]);
		}
		/* @var $user User */
		$user = $forgot->user;
		if (!$user instanceof User) {
			return $forgot->theme('forgot/not-found', [
				"message_type" => "user-not-found",
				"message" => $locale->__("User not found."),
			]);
		}
		$session = $this->application->session($this->request);
		if ($forgot->id() !== $session->forgot) {
			return $forgot->theme('same-browser');
		}
		if (!$forgot->member_is_empty('updated')) {
			return $forgot->theme("already");
		}

		return $this->control($this->widget_factory(Control_ForgotReset::class)->validate_token($token));
	}
}
