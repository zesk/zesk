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
		$this->control($this->widget_factory(Control_Forgot::class));
	}
	
	/**
	 * 
	 */
	function action_unknown() {
		$this->template->content = $this->application->theme('forgot/unknown');
	}
	
	/**
	 * 
	 */
	function action_sent() {
		$this->template->content = $this->application->theme('forgot/sent');
	}
	
	/**
	 * 
	 * @param unknown $hash
	 */
	function action_validate($hash) {
		if (!preg_match('/[0-9a-f]{32}/i', $hash)) {
			$this->template->content = $this->application->theme('forgot/not-found', array(
				"message_type" => "invalid-token",
				"message" => "Validation key is not valid."
			));
			return;
		}
		/* @var $forgot Forgot */
		$forgot = $this->object_factory(Forgot::class, array(
			"code" => $hash
		))->find();
		if (!$forgot) {
			$this->template->content = $this->application->theme('forgot/not-found', array(
				"message_type" => "not-found",
				"message" => __("Unable to find the validation token.")
			));
			return;
		}
		/* @var $user User */
		$user = $forgot->user;
		if (!$user instanceof User) {
			$this->template->content = $forgot->theme('forgot/not-found', array(
				"message_type" => "user-not-found",
				"message" => "User not found."
			));
			return;
		}
		$session = $this->application->session();
		if ($forgot->id() !== $session->forgot) {
			$this->template->content = $forgot->theme('same-browser');
			return;
		}
		if (!$forgot->member_is_empty('updated')) {
			$this->template->content = $forgot->theme("already");
			return;
		}
		
		$forgot->validated();
		
		$this->template->content = $forgot->theme("success");
	}
}
