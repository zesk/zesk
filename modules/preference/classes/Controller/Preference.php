<?php
/**
 * 
 */
namespace zesk;

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
	
	/**
	 * 
	 * @var array
	 */
	protected $whitelist = null;
	
	/**
	 * 
	 * @return string
	 */
	public function _whitelist() {
		return $this->application->application_root("etc/preference-whitelist.txt");
	}
	
	/**
	 * 
	 */
	public function save_preferences() {
		file_put_contents($this->_whitelist(), implode("\n", array_keys($this->whitelist)));
	}
	
	/**
	 * 
	 * @param string $action
	 * @param string $arg
	 * @return NULL[]|unknown[]
	 */
	public function arguments_getset($action, $arg) {
		if ($this->whitelist === null) {
			$path = $this->whitelist = array_flip(arr::clean(explode("\n", File::contents($this->_whitelist(), "")), ""));
			if ($this->application->development()) {
				zesk()->hooks->add("exit", array(
					$this,
					'save_preferences'
				));
			}
		}
		if (!array_key_exists($arg, $this->whitelist)) {
			if ($this->application->development()) {
				$this->whitelist[$arg] = true;
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
	
	/**
	 * 
	 * @param string $type
	 * @return \zesk\Response|boolean
	 */
	public function action_getset($type) {
		if ($type === null) {
			return $this->json(array(
				"status" => false,
				"message" => __("Invalid preference")
			));
		}
		$user = $this->application->user();
		if (!$user->authenticated()) {
			return $this->json(array(
				"status" => false,
				"message" => __("Not authenticated")
			));
		}
		if ($this->request->is_post()) {
			$value = PHP::autotype($this->request->get('value'));
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
