<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\AuthenticationException;
use zesk\Locale\Locale;
use zesk\Router\RouterFile;

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

	public function hookSources(): array {
		return array_merge([$this->zeskHome('test/classes')], parent::hookSources());
	}

	public function beforeConfigure(): void {
		$this->router = RouterFile::load($this->router, File::setExtension(__FILE__, 'router'));
		parent::beforeConfigure();
		$this->hooks->registerHook(Application::HOOK_COMMAND, function (Command $command): void {
			$app = $command->application;
			$app->hooksCalled[Application::HOOK_COMMAND] = $command;
		});
		$this->hooks->registerHook(Application::HOOK_LOCALE, $this->registrationBasedObjectHook(...));
		$id = __METHOD__;
		$this->hooks->registerFilter(Application::HOOK_MAINTENANCE, function (self $app, array $set) use ($id): array {
			if ($set['maintenance']) {
				$value = $app->option('preventMaintenance');
				if ($value === 'throw') {
					throw new AuthenticationException();
				}
				if ($value) {
					$set['maintenance'] = false;
				}
			}
			$set[$id] = true;
			return $set;
		});
	}

	public function registrationBasedObjectHook(TestApplication $test, Locale $locale): void {
		$this->hooksCalled[Application::HOOK_LOCALE][] = 'object';
	}

	/**
	 * @param TestApplication $test
	 * @param Locale $locale
	 * @return void
	 */
	#[HookMethod(handles: Application::HOOK_LOCALE)]
	public static function attributeBasedHookTest(TestApplication $test, Locale $locale): void {
		$test->hooksCalled[Application::HOOK_LOCALE][] = 'static';
	}
}
