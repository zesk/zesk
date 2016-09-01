<?php

/**
 * Output useful globals and settings which affect Zesk runtime.
 *
 * @category Debugging
 * @param array $arguments
 */
class Command_Info extends Command {
	protected $option_types = array(
		'help' => 'boolean',
		'computer-labels' => 'boolean',
		'format' => 'string'
	);
	protected $option_help = array(
		'help' => 'This help',
		'computer-labels' => 'Show computer labels',
		'format' => "output format: text (default), html, php, serialize, json"
	);
	static $human_names = array(
		'zesk_version_release' => 'Zesk Version',
		'zesk_version_string' => 'Zesk Version String',
		'zesk_application_root' => 'Zesk Application Root',
		'zesk_root' => 'Zesk Root',
		'database::url' => 'Database URL (Primary)',
		'database::default' => 'Database Name (Primary)',
		'Application::theme_path' => 'Zesk Theme Path',
		'enable_dl' => 'Enable Dynamic Libraries',
		'php_ini' => 'php.ini Path',
		'command_path' => 'Shell Command Path',
		'zesk_command_path' => 'Zesk Command Path',
		'display_startup_errors' => 'Display Startup Errors',
		'error_log' => 'PHP Error Log',
		'Application::class' => 'Zesk Application Class',
		'conf::loaded' => 'Loaded Configuration Files'
	);
	function run() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$app = $this->application;
		
		$info['zesk_version_release'] = zesk\Version::release();
		$info['zesk_version_string'] = zesk\Version::string();
		$info['zesk_root'] = ZESK_ROOT;
		$info['zesk_application_root'] = $app->application_root();
		$info['Application::class'] = $app->application_class();
		$info['Database::default'] = $default = Database::database_default();
		$info['Database::url'] = url::remove_password(Database::register($default));
		$info['command_path'] = implode(":", $app->command_path());
		$info['Application::theme_path'] = implode(":", $app->theme_path());
		$info['zesk_command_path'] = implode(":", $app->zesk_command_path());
		$info['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
		$info['php_ini'] = get_cfg_var('cfg_file_path');
		$info['display_startup_errors'] = to_bool(ini_get('display_startup_errors')) ? 'true' : 'false';
		$info['error_log'] = ini_get('error_log');
		$info['conf::loaded'] = to_array($zesk->configuration->path_get('conf::loaded'));
		
		$module_info = $app->module->all_hook_array("info", array(), array());
		$info = array_merge($info, arr::key_value($module_info, null, "value"));
		foreach ($module_info as $code_name => $settings) {
			$human_names[$code_name] = avalue($settings, "title", $code_name);
		}
		
		if (!$this->option_bool('computer-labels')) {
			$info = arr::map_keys($info, self::$human_names);
		}
		echo $this->render_format($info, $this->option("format"));
	}
}