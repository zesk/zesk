<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 * Output useful globals and settings which affect Zesk runtime.
 *
 * @param array $arguments
 * @category Debugging
 */
class Command_Info extends Command_Base {
	/**
	 * @var array
	 */
	protected array $shortcuts = ['info', 'i'];

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'help' => 'boolean', 'computer-labels' => 'boolean', 'format' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	protected array $option_help = [
		'help' => 'This help', 'computer-labels' => 'Show computer labels',
		'format' => 'output format: text (default), html, php, serialize, json',
	];

	/**
	 *
	 * @todo FINISH DOING THIS FOR ALL CONSTANTS BELOW
	 *
	 * @var string
	 */
	public const ZESK_VERSION_RELEASE = 'release';

	/**
	 *
	 * @var string
	 */
	public const CONFIGURATION_FILES_LOADED = 'configurationFilesLoaded';

	/**
	 *
	 * @var string
	 */
	public const VERSION_RELEASE_STRING = 'releaseString';

	/**
	 *
	 *
	 * @see Command::run()
	 */
	public function run(): int {
		$app = $this->application;
		$appScope = Application::class;

		$info[$appScope][Application::OPTION_VERSION] = $app->version();
		$info[Kernel::class][self::ZESK_VERSION_RELEASE] = Version::release();
		$info[Kernel::class][self::VERSION_RELEASE_STRING] = Version::string($this->application->locale);
		$info[$appScope]['zeskRoot'] = ZESK_ROOT;
		$info[$appScope][Application::OPTION_PATH] = $app->path();
		$info[$appScope][Application::OPTION_APPLICATION_CLASS] = $app->applicationClass();
		$info[$appScope][Application::OPTION_COMMAND_PATH] = $app->commandPath();
		$info[$appScope]['themePath'] = $app->themes->themePath();
		$info[$appScope][Application::OPTION_ZESK_COMMAND_PATH] = $app->zeskCommandPath();

		$info['PHP']['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
		$info['PHP']['ini_path'] = PHP::ini_path();
		$info['PHP']['display_startup_errors'] = toBool(ini_get('display_startup_errors'));
		$info['PHP']['error_log'] = ini_get('error_log');
		$variables = $app->loader->variables();
		$info[$appScope][self::CONFIGURATION_FILES_LOADED] = toArray($variables['processed'] ?? []);

		$module_info = $app->modules->allHookArguments('info', [
			[],
		], []);
		$info = array_merge($info, ArrayTools::extract($module_info, null, 'value'));

		$format = $this->optionString('format', self::FORMAT_TEXT);
		if ($format === self::FORMAT_TEXT) {
			$info = ArrayTools::keysFlatten($info, '::', false);
		}
		$this->renderFormat($info, $format);
		return 0;
	}
}
