<?php
class Controller_Forgot extends Controller_Template {
	function action_index() {
		$this->control($this->widget_factory("Control_Forgot"));
	}
	function action_unknown() {
		$this->template->content = $this->application->theme('forgot/unknown');
	}
	function action_sent() {
		$this->template->content = $this->application->theme('forgot/sent');
	}
	function action_validate($hash) {
		/* @var $forgot Forgot */
		$forgot = $this->object_factory('Forgot', array(
			"code" => $hash
		))->find();
		if (!$forgot) {
			$this->template->content = $this->application->theme('forgot/not-found');
			return;
		}
		/* @var $user User */
		$user = $forgot->user;
		if (!$user instanceof User) {
			$this->template->content = $this->application->theme('forgot/not-found', array(
				"message" => "User not found."
			));
			return;
		}
		$session = Session::instance();
		if ($forgot->id() !== $session->forgot) {
			$this->template->content = $this->application->theme('forgot/same-browser');
			return;
		}
		if (!$forgot->member_is_empty('updated')) {
			$this->template->content = $this->application->theme("forgot/already");
			return;
		}

		$forgot->validated();

		$this->template->content = $this->application->theme("forgot/success");
	}
}
