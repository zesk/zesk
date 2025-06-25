<?php
declare(strict_types=1);
/**
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\Command;
use zesk\Directory;
use zesk\Doctrine\User;
use zesk\Exception\AuthenticationException;
use zesk\File;
use zesk\FilterMethod;
use zesk\HookMethod;
use zesk\Locale\Locale;
use zesk\Model;
use zesk\Request;
use zesk\Router\RouterFile;

/**
 * @see ApplicationTestBase
 */
class TestApplication extends Application
{
	public const TEST_MAINTENANCE_FILE = './.testMaintenance.json';

	public string $file = __FILE__;

	public array $hooksCalled = [];

	public array $classAliases = [
		Request::class => TestRequest::class,
	];

	/**
	 * @return string
	 */
	public function id(): string
	{
		if (!$this->hasOption('id')) {
			$id = self::class . '-' . microtime(true) * 1000;
			$this->setOption('id', $id);
			return $id;
		}
		return $this->option('id');
	}

	public function hookSources(): array
	{
		return array_merge([$this->zeskHome('tests/testZesk')], parent::hookSources());
	}

	public function beforeConfigure(): void
	{
		$this->setOptions([
			self::OPTION_MAINTENANCE_FILE => self::TEST_MAINTENANCE_FILE,
		], false);
		$this->router = RouterFile::load($this->router, File::setExtension(__FILE__, 'router'));
		parent::beforeConfigure();
		$this->hooks->registerHook(Application::HOOK_COMMAND, function (Command $command): void {
			$app = $command->application;
			$app->hooksCalled[Application::HOOK_COMMAND] = $command;
		});
		$this->hooks->registerHook(Application::HOOK_LOCALE, $this->registrationBasedObjectHook(...));
		$id = __METHOD__;
		$this->hooks->registerFilter(Application::FILTER_MAINTENANCE, function (self $app, array $set) use ($id): array {
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
		Directory::depend($this->path('etc'));
	}

	public function registrationBasedObjectHook(TestApplication $test, Locale $locale): void
	{
		$this->hooksCalled[Application::HOOK_LOCALE][] = 'object';
	}

	/**
	 * @param TestApplication $test
	 * @param Locale $locale
	 * @return void
	 */
	#[HookMethod(handles: Application::HOOK_LOCALE)]
	public static function attributeBasedHookTest(TestApplication $test, Locale $locale): void
	{
		$test->hooksCalled[Application::HOOK_LOCALE][] = 'static';
	}

	#[FilterMethod(User::HOOK_CAN)]
	public function canFilter(User $user, array|string $actions, Model|null $model = null, array $options = [], bool $permission = false): bool
	{
		return $user->optionBool('can', $permission);
	}
}
