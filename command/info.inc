<?php

/**
 * Output useful globals and settings which affect Zesk runtime.
 *
 * @category Debugging
 * @param array $arguments
 */
use zesk\Database;
use zesk\URL;
use zesk\arr;

/**
 * 
 * @author kent
 *
 */
class Command_Info extends Command {
	/**
	 * 
	 * @var array
	 */
	protected $option_types = array(
		'help' => 'boolean',
		'computer-labels' => 'boolean',
		'format' => 'string'
	);
	
	/**
	 * 
	 * @var array
	 */
	protected $option_help = array(
		'help' => 'This help',
		'computer-labels' => 'Show computer labels',
		'format' => "output format: text (default), html, php, serialize, json"
	);
	
	/**
	 * 
	 * @var array
	 */
	static $human_names = array(
		'zesk_version_release' => 'Zesk Version',
		'zesk_version_string' => 'Zesk Version String',
		'zesk_application_root' => 'Zesk Application Root',
		'zesk_root' => 'Zesk Root',
		'database::url' => 'Database URL (Primary)',
		'database::default' => 'Database Name (Primary)',
		'Application::theme_path' => 'Application Theme Path',
		'enable_dl' => 'Enable Dynamic Libraries',
		'php_ini' => 'php.ini Path',
		'command_path' => 'Shell Command Path',
		'zesk_command_path' => 'Zesk Command Path',
		'display_startup_errors' => 'Display Startup Errors',
		'error_log' => 'PHP Error Log',
		'Application::class' => 'Zesk Application Class',
		'conf::loaded' => 'Loaded Configuration Files'
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	function run() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$app = $this->application;
		
		$info['zesk_version_release'] = zesk\Version::release();
		$info['zesk_version_string'] = zesk\Version::string();
		$info['zesk_root'] = ZESK_ROOT;
		$info['zesk_application_root'] = $app->application_root();
		$info['Application::class'] = $app->application_class();
		$info['zesk\Database::default'] = $default = zesk\Database::database_default();
		$info['zesk\Database::url'] = URL::remove_password(zesk\Database::register($default));
		$info['command_path'] = $app->command_path();
		$info['Application::theme_path'] = $app->theme_path();
		$info['zesk_command_path'] = $app->zesk_command_path();
		$info['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
		$info['php_ini'] = get_cfg_var('cfg_file_path');
		$info['display_startup_errors'] = to_bool(ini_get('display_startup_errors')) ? 'true' : 'false';
		$info['error_log'] = ini_get('error_log');
		$info['conf::loaded'] = to_array($zesk->configuration->path_get('conf::loaded'));
		
		$module_info = $app->modules->all_hook_arguments("info", array(), array());
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
