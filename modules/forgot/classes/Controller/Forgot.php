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
	 * @param unknown $hash
	 */
	function action_validate($hash) {
		if (!preg_match('/[0-9a-f]{32}/i', $hash)) {
			return $this->application->theme('forgot/not-found', array(
				"message_type" => "invalid-token",
				"message" => "Validation key is not valid."
			));
		}
		/* @var $forgot Forgot */
		$forgot = $this->application->orm_factory(Forgot::class, array(
			"code" => $hash
		))->find();
		if (!$forgot) {
			return $this->application->theme('forgot/not-found', array(
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
		
		$forgot->validated();
		
		return $forgot->theme("success");
	}
}
