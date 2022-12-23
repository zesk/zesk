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

	public function run(): int {
		if ($this->hasArgument()) {
			$arg = $this->getArgument('value');
			$bool = toBool($arg, null);
			if ($bool === null) {
				$this->setOption('message', $arg);
				$this->application->setMaintenance(true);
				$this->log("Maintenance enabled with message \"$arg\"", [
					'arg' => $arg,
				]);
			} else {
				$this->application->setMaintenance($bool);
				$this->log('Maintenance ' . ($bool ? 'enabled' : 'disabled'));
			}
		}
		if ($this->application->maintenance()) {
			return 0;
		}
		return 1;
	}

	/**
	 * Pass values to store as part of the system globals upon maintenance
	 *
	 * @param Application $app
	 * @param array $values
	 * @return array
	 */
	public function maintenance_context(Application $app, array $values): array {
		assert($app->isConfigured());
		$values['message'] = $this->optionString('message');
		return $values;
	}
}
