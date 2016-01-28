<?php

/**
 * Very similar to Controller_Setting - refactor both
 *
 * @author kent
 */
class Controller_Preference extends Controller {
	/**
	 * Method to use as default action in this Controller. Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_action = "action_getset";
	/**
	 * Method to use as default action in this Controller. Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_arguments = "arguments_getset";

	static $whitelist = null;

	public static function _whitelist() {
		return zesk::application_root("etc/preference-whitelist.txt");
	}

	public static function save_preferences() {
		file_put_contents(self::_whitelist(), implode("\n", array_keys(self::$whitelist)));
	}

	public function arguments_getset($action, $arg) {
		if (self::$whitelist === null) {
			$path = self::$whitelist = array_flip(arr::clean(explode("\n", file::contents(self::_whitelist(), "")), ""));
			if ($this->application->development()) {
				zesk::add_hook("exit", __CLASS__ . '::save_preferences');
			}
		}
		if (!array_key_exists($arg, self::$whitelist)) {
			if ($this->application->development()) {
				self::$whitelist[$arg] = true;
			} else {
				return array(
					null
				);
			}
		}
		return array(
			$arg
		);
	}

	public function action_getset($type) {
		if ($type === null) {
			return $this->json(array(
				"status" => false,
				"messge" => __("Invalid preference")
			));
		}
		$user = User::instance();
		if (!$user->authenticated()) {
			return $this->json(array(
				"status" => false,
				"messge" => __("Not authenticated")
			));
		}
		if ($this->request->is_post()) {
			$value = zesk::autotype($this->request->get('value'));
			if (Preference::user_set($user, $type, $value)) {
				return $this->json(array(
					"status" => true
				));
			}
			return $this->json(array(
				"status" => false,
				"message" => __("Can not set preference {type}", array(
					"type" => $type
				))
			));
		}
		return $this->json(array(
			"value" => Preference::user_get($user, $type)
		));
	}
}