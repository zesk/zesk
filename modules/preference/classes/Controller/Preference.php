<?php declare(strict_types=1);
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
		return $this->application->path("etc/preference-whitelist.txt");
	}

	/**
	 *
	 */
	public function save_preferences(): void {
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
			$path = $this->whitelist = array_flip(ArrayTools::clean(explode("\n", File::contents($this->_whitelist(), "")), ""));
			if ($this->application->development()) {
				// TODO Possible security hole here - if someone outside can set development, they could possibly overwrite User preferences how they wish.
				// Possibly set a global flag instead?
				$this->application->hooks->add("exit", [
					$this,
					'save_preferences',
				]);
			}
		}
		if (!array_key_exists($arg, $this->whitelist)) {
			if ($this->application->development()) {
				$this->whitelist[$arg] = true;
			} else {
				return [
					null,
				];
			}
		}
		return [
			$arg,
		];
	}

	/**
	 *
	 * @param string $type
	 * @return \zesk\Response|boolean
	 */
	public function action_getset($type) {
		$locale = $this->application->locale;
		if ($type === null) {
			$extras = [];
			if ($this->optionBool('debug')) {
				$extras += [
					'type' => $type,
					'route' => [
						'indexed' => $this->route->arguments_indexed(),
						'named' => $this->route->arguments_named(),
					],
					'path' => $this->request->path(),
					'whitelist' => array_keys($this->whitelist),
				];
			}
			return $this->json([
				"status" => false,
				"message" => $locale->__("Invalid preference"),
			] + $extras);
		}
		$user = $this->application->user($this->request);
		if (!$user || !$user->authenticated($this->request)) {
			$this->response->status(Net_HTTP::STATUS_UNAUTHORIZED, $message_en = "Not authenticated");
			return $this->json([
				"status" => false,
				"actions" => [
					"user-not-authenticated",
				],
				"message" => $locale->__($message_en),
			]);
		}
		if ($this->request->is_post()) {
			$value = PHP::autotype($this->request->get('value'));
			if (Preference::user_set($user, $type, $value)) {
				return $this->json([
					"status" => true,
				]);
			}
			return $this->json([
				"status" => false,
				"message" => $locale->__("Can not set preference {type}", [
					"type" => $type,
				]),
			]);
		}
		return $this->json([
			"value" => Preference::user_get($user, $type),
		]);
	}
}
