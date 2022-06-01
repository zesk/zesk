<?php declare(strict_types=1);
namespace zesk;

/**
 * Turn maintenance on or off
 *
 * @category Management
 * @author kent
 *
 */
class Command_Maintenance extends Command_Base {
	protected function initialize(): void {
		parent::initialize();
		$this->application->hooks->add(Application::class . '::maintenance_context', [
			$this,
			'maintenance_context',
		]);
	}

	public function run() {
		if (!$this->has_arg()) {
			echo $this->application->maintenance();
			return 0;
		}
		$arg = $this->get_arg('value');
		$this->message = $arg;
		$bool = toBool($arg, null);
		if ($bool === null) {
			$this->application->maintenance(true);
			$this->log("Maintenance enabled with message \"$arg\"", [
				'arg' => $arg,
			]);
		} else {
			$this->application->maintenance($bool);
			$this->log('Maintenance ' . ($bool ? 'enabled' : 'disabled'));
		}
	}

	/**
	 * Pass values to store as part of the system globals upon maintenance
	 *
	 * @param Application $app
	 * @param array $values
	 * @return array
	 */
	public function maintenance_context(Application $app, array $values) {
		if (is_string($this->message)) {
			$values['message'] = $this->message;
		}
		return $values;
	}
}
