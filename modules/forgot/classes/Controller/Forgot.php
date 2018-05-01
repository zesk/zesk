<?php
/**
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_Forgot extends Controller_Theme {
	/**
	 *
	 */
	function action_index() {
		return $this->control($this->widget_factory(Control_Forgot::class));
	}

	/**
	 *
	 */
	function action_unknown() {
		return $this->application->theme('forgot/unknown');
	}

	/**
	 *
	 */
	function action_sent() {
		return $this->application->theme('forgot/sent');
	}

	/**
	 *
	 */
	function action_complete($token) {
		return $this->application->theme('forgot/complete');
	}

	/**
	 *
	 * @param unknown $token
	 */
	function action_validate($token) {
		$prefer_json = $this->request->prefer_json();
		if (!preg_match('/[0-9a-f]{32}/i', $token)) {
			$args = array(
				"token" => $token,
				"message_type" => "invalid-token",
				"message" => __("The validation token passed in the URL is an incorrect format.")
			);
			return $this->application->theme('forgot/not-valid', $args);
		}
		/* @var $forgot Forgot */
		$forgot = $this->application->orm_factory(Forgot::class, array(
			"code" => $token
		))->find();
		if (!$forgot) {
			return $this->application->theme('forgot/not-found', array(
				"token" => $token,
				"message_type" => "not-found",
				"message" => __("Unable to find the validation token.")
			));
		}
		/* @var $user User */
		$user = $forgot->user;
		if (!$user instanceof User) {
			return $forgot->theme('forgot/not-found', array(
				"message_type" => "user-not-found",
				"message" => "User not found."
			));
		}
		$session = $this->application->session($this->request);
		if ($forgot->id() !== $session->forgot) {
			return $forgot->theme('same-browser');
		}
		if (!$forgot->member_is_empty('updated')) {
			return $forgot->theme("already");
		}

		return $this->control($this->widget_factory(Control_ForgotReset::class));

		// 		$forgot->validated();

		// 		return $forgot->theme("success");
	}
}
