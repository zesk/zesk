<?php
declare(strict_types=1);

namespace zesk;

/**
 * @see ApplicationTest
 */
class TestApplication extends Application {
	public const TEST_MAINTENANCE_FILE = './.testMaint.json';

	protected array $options = [
		self::OPTION_MAINTENANCE_FILE => self::TEST_MAINTENANCE_FILE,
	];

	public string $file = __FILE__;

	public array $hooksCalled = [];

	public array $class_aliases = [
		Request::class => TestRequest::class,
	];

	public function beforeConfigure(): void {
		parent::beforeConfigure();
		$this->hooks->add(Application::class . '::command', function ($_app, $command): void {
			$_app->hooksCalled[Application::class . '::command'] = $command;
		});
		$this->hooks->add(Command::class . '::replacedWith', function ($oldCommand, $command): void {
			$command->application->hooksCalled[Command::class . '::replacedWith'] = $command;
		});
		$this->hooks->add(Application::class . '::singleton_zesk_TestModel', function ($app): TestModel {
			$app->hooksCalled[Application::class . '::singleton_zesk_TestModel'] = true;
			return new TestModel($app);
		});
		$this->hooks->add(Application::class . '::setLocale', function ($app, $locale): void {
			$app->hooksCalled[Application::class . '::setLocale'] = $locale;
		});
		$this->hooks->add(Application::class . '::setMaintenance', function (Application $app, bool $set): bool {
			if ($set) {
				$value = $app->option('preventMaintenance');
				if ($value === 'throw') {
					throw new Exception_Authentication();
				}
				if ($value === true) {
					return false;
				}
			}
			return true;
		});
	}
}
