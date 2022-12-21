<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage controller
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Controller_Control extends Controller {
	/**
	 * Method to use as default action in this Controller. Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_action = 'action_control';

	/**
	 * Method to use as default action in this Controller. Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_arguments = null;

	private static $allowed = null;

	public function allowed_control($control) {
		if (!is_array(self::$allowed)) {
			self::$allowed = array_change_key_case(ArrayTools::keysFromValues($this->optionIterable('allowed_controls'), true));
		}
		return self::$allowed[strtolower($control)] ?? false;
	}

	public function action_control($control, $name, $input) {
		// We don't just instantiate classes, must be in approved list.
		$control = "Control_$control";
		if (!$this->allowed_control($control)) {
			$this->application->logger->error('User requested prohibited control: {control}', [
				'control' => $control,
			]);
			$this->error_404('Control prohibited.');
			return;
		}

		$result = [
			'content' => $this->widgetFactory($control)
				->names($name, null, $input)
				->setRequest($this->request)->response($this->response)
				->json()
				->execute(),
		];
		$result += $this->response->toJSON();
		return $this->json($result);
	}
}
