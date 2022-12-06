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
	public const zesk_version_release = "zesk\Version::release";

	/**
	 *
	 * @var string
	 */
	public const configuration_files_loaded = 'configuration_files_loaded';

	/**
	 *
	 * @var string
	 */
	public const zesk_version_string = "zesk\Version::release_string";

	/**
	 *
	 * @var string
	 */
	public const zesk_root = 'zesk_root';

	/**
	 *
	 * @var string
	 */
	public const zesk_application_root = 'zesk_application_root';

	/**
	 *
	 * @var string
	 */
	public const zesk_application_class = "zesk\Kernel::application_class";

	/**
	 *
	 * @var string
	 */
	public const zesk_application_theme_path = "zesk\Application::theme_path";

	/**
	 *
	 * @var string
	 */
	public const command_path = 'command_path';

	/**
	 *
	 * @var string
	 */
	public const zesk_autoload_path = 'zesk\\Autoloader::path';

	/**
	 *
	 * @var array
	 */
	public static $human_names = [
		self::APPLICATION_VERSION => 'Application Version', self::zesk_version_release => 'Zesk Version',
		self::zesk_version_string => 'Zesk Version String',
		self::zesk_application_theme_path => 'Application Theme Path',
		self::zesk_application_root => 'Zesk Application Root', self::zesk_root => 'Zesk Root',
		'enable_dl' => 'Enable Dynamic Libraries', 'php_ini' => 'php.ini Path',
		self::command_path => 'Shell Command Path', 'zesk_command_path' => 'Zesk Command Path',
		self::zesk_autoload_path => 'Zesk Autoload Path', 'display_startup_errors' => 'Display Startup Errors',
		'error_log' => 'PHP Error Log', self::zesk_application_class => 'Zesk Application Class',
		self::configuration_files_loaded => 'Loaded Configuration Files',
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
	public function run() {
		$app = $this->application;

		$info[self::APPLICATION_VERSION] = $app->version();
		$info[self::zesk_version_release] = Version::release();
		$info[self::zesk_version_string] = Version::string($this->application->locale);
		$info[self::zesk_root] = ZESK_ROOT;
		$info[self::zesk_application_root] = $app->path();
		$info[self::zesk_application_class] = $app->applicationClass();
		$info[self::command_path] = $app->commandPath();
		$info[self::zesk_application_theme_path] = $app->themePath();
		$info['zesk_command_path'] = $app->zesk_command_path();
		$info[self::zesk_autoload_path] = $app->autoloader->path();
		$info['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
		$info['php_ini'] = get_cfg_var('cfg_file_path');
		$info['display_startup_errors'] = toBool(ini_get('display_startup_errors')) ? 'true' : 'false';
		$info['error_log'] = ini_get('error_log');
		$variables = $app->loader->variables();
		$info[self::configuration_files_loaded] = toArray($variables['processed'] ?? []);

		$module_info = $app->modules->all_hook_arguments('info', [
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
		$this->renderFormat($info, $this->option('format'));
		return 0;
	}
}
