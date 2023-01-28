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
	public const ZESK_VERSION_RELEASE = Version::class . '::release';

	/**
	 *
	 * @var string
	 */
	public const CONFIGURATION_FILES_LOADED = 'configuration_files_loaded';

	/**
	 *
	 * @var string
	 */
	public const VERSION_RELEASE_STRING = Version::class . '::release_string';

	/**
	 *
	 * @var string
	 */
	public const ZESK_ROOT = Application::class . '::' . Application::OPTION_ZESK_ROOT;

	/**
	 *
	 * @var string
	 */
	public const APPLICATION_PATH = Application::class . '::' . Application::OPTION_PATH;

	/**
	 *
	 * @var string
	 */
	public const APPLICATION_CLASS = Application::class . '::' . Application::OPTION_APPLICATION_CLASS;

	/**
	 *
	 * @var string
	 */
	public const APPLICATION_THEME_PATH = Application::class . '::themePath';

	/**
	 *
	 * @var string
	 */
	public const COMMAND_PATH = 'command_path';

	/**
	 *
	 * @var string
	 */
	public const ZESK_AUTOLOAD_PATH = Autoloader::class . '::path';

	/**
	 *
	 * @var array
	 */
	public static array $human_names = [
		self::APPLICATION_VERSION => 'Application Version', self::ZESK_VERSION_RELEASE => 'Zesk Version',
		self::VERSION_RELEASE_STRING => 'Zesk Version String', self::APPLICATION_THEME_PATH => 'Application Theme Path',
		self::APPLICATION_PATH => 'Zesk Application Root', self::ZESK_ROOT => 'Zesk Root',
		'enable_dl' => 'Enable Dynamic Libraries', 'php_ini' => 'php.ini Path',
		self::COMMAND_PATH => 'Shell Command Path', 'zeskCommandPath' => 'Zesk Command Path',
		self::ZESK_AUTOLOAD_PATH => 'Zesk Autoload Path', 'display_startup_errors' => 'Display Startup Errors',
		'error_log' => 'PHP Error Log', self::APPLICATION_CLASS => 'Zesk Application Class',
		self::CONFIGURATION_FILES_LOADED => 'Loaded Configuration Files',
	];

	/**
	 *
	 * @var string
	 */
	public const APPLICATION_VERSION = "zesk\Application::version";

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	public function run(): int {
		$app = $this->application;

		$info[self::APPLICATION_VERSION] = $app->version();
		$info[self::ZESK_VERSION_RELEASE] = Version::release();
		$info[self::VERSION_RELEASE_STRING] = Version::string($this->application->locale);
		$info[self::ZESK_ROOT] = ZESK_ROOT;
		$info[self::APPLICATION_PATH] = $app->path();
		$info[self::APPLICATION_CLASS] = $app->applicationClass();
		$info[self::COMMAND_PATH] = $app->commandPath();
		$info[self::APPLICATION_THEME_PATH] = $app->themes->themePath();
		$info['zeskCommandPath'] = $app->zeskCommandPath();
		$info[self::ZESK_AUTOLOAD_PATH] = $app->autoloader->path();
		$info['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
		$info['php_ini'] = PHP::ini_path();
		$info['display_startup_errors'] = toBool(ini_get('display_startup_errors'));
		$info['error_log'] = ini_get('error_log');
		$variables = $app->loader->variables();
		$info[self::CONFIGURATION_FILES_LOADED] = toArray($variables['processed'] ?? []);

		$module_info = $app->modules->allHookArguments('info', [
			[],
		], []);
		$info = array_merge($info, ArrayTools::extract($module_info, null, 'value'));
		$human_names = [];
		foreach ($module_info as $code_name => $settings) {
			$human_names[$code_name] = $settings['title'] ?? $code_name;
		}

		if (!$this->optionBool('computer-labels')) {
			$info = ArrayTools::keysMap($info, $human_names + self::$human_names);
		}
		$this->renderFormat($info, $this->optionString('format'));
		return 0;
	}
}
