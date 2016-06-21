<?php
/**
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
	public static function cron_cluster_minute() {
		$query = Object::class_query_delete("Forgot")->where("Created|<=", Timestamp::now()->add_unit("second", -abs(zesk::geti("Module_Forgot::expire_seconds", 3600))));
		$query->execute();
		$affected_rows = $query->affected_rows();
		if ($affected_rows > 0) {
			log::notice("Forgot::cron_minute deleted {affected_rows} forgotten rows", compact("affected_rows"));
		}
	}
	public function notify(Request $request) {
		$user = $this->user;
		$variables = array(
			"user_login" => $user->login(),
			"user_email" => $user->email()
		);
		
		$variables += arr::kunprefix($this->options, "notify_", true);
		$variables += arr::kprefix($this->members(), "forgot_");
		$variables += arr::kprefix($user->members(), "user_");
		$variables += arr::kprefix($request->variables(), "request_");
		$variables += arr::kprefix($request->url_parts(), "url_");
		
		$variables = $this->hook_array("notify_variables", array(
			$variables
		), $variables);
		$mail = $this->hook("notify", $variables);
		if ($mail instanceof Mail) {
			return $mail;
		}
		$mail_options = mail::load_theme($this->application, "forgot/notify", $variables);
		return mail::mulitpart_send($mail_options);
	}
	public function validated() {
		$user = $this->user;
		$user->password($this->new_password)->store();
		$this->updated = "now";
		$this->store();
		$this->hook("validated");
		$query = Object::class_query_update(__CLASS__);
		$query->value("*updated", $query->sql()->now());
		$query->where(array(
			"user" => $user,
			"updated" => null
		));
		$query->execute();
		return $this;
	}
}
