<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */


namespace zesk\Command;

use zesk\Application;
use zesk\HookMethod;
use zesk\Types;

/**
 * Turn maintenance on or off, with an optional message.
 *
 * @category Management
 * @author kent
 *
 */
class Maintenance extends SimpleCommand
{
	protected array $shortcuts = ['maintenance'];

	protected array $option_types = [
		'*' => 'string',
	];

	public function run(): int
	{
		if ($this->hasArgument()) {
			$arg = $this->getArgument('message');
			$bool = Types::toBool($arg, null);
			if ($bool === null) {
				$this->setOption('message', $arg);
				$this->application->setMaintenance(true);
				$this->info("Maintenance enabled with message \"$arg\"", [
					'arg' => $arg,
				]);
			} else {
				$this->application->setMaintenance($bool);
				$this->info('Maintenance ' . ($bool ? 'enabled' : 'disabled'));
			}
			return 0;
		}

		if ($this->application->maintenance()) {
			$message = $this->application->optionPath([Application::OPTION_MAINTENANCE, 'message']);
			if ($message) {
				echo "$message\n";
			}
			return 0;
		}
		return 1;
	}

	/**
	 * Pass values to store as part of the system globals upon maintenance
	 *
	 * Note this will only be called if it exists in the application as an object, usually as `->command()`
	 *
	 * @param Application $app
	 * @param array $values
	 * @return array
	 * @see self::maintenanceEnabled()
	 */
	#[FilterMethod(handles: Application::FILTER_MAINTENANCE)]
	public function maintenanceEnabled(Application $app, array $values): array
	{
		assert($app->isConfigured());
		$values['message'] = $this->optionString('message');
		return $values;
	}
}
