<?php

/**
 *
 */
namespace zesk;

/**
 * Output useful globals and settings which affect Zesk runtime.
 *
 * @category Debugging
 * @param array $arguments
 */
class Command_Info extends Command {
    /**
     *
     * @var array
     */
    protected $option_types = array(
        'help' => 'boolean',
        'computer-labels' => 'boolean',
        'format' => 'string',
    );

    /**
     *
     * @var array
     */
    protected $option_help = array(
        'help' => 'This help',
        'computer-labels' => 'Show computer labels',
        'format' => "output format: text (default), html, php, serialize, json",
    );

    /**
     *
     * @todo FINISH DOING THIS FOR ALL CONSTANTS BELOW
     *
     * @var string
     */
    const zesk_version_release = "zesk\Version::release";

    /**
     *
     * @var string
     */
    const configuration_files_loaded = "configuration_files_loaded";

    /**
     *
     * @var string
     */
    const zesk_version_string = "zesk\Version::release_string";

    /**
     *
     * @var string
     */
    const zesk_root = "zesk_root";

    /**
     *
     * @var string
     */
    const zesk_application_root = "zesk_application_root";

    /**
     *
     * @var string
     */
    const zesk_application_class = "zesk\Kernel::application_class";

    /**
     *
     * @var string
     */
    const zesk_application_theme_path = "zesk\Application::theme_path";

    /**
     *
     * @var string
     */
    const command_path = "command_path";

    /**
     *
     * @var string
     */
    const zesk_autoload_path = "zesk\\Autoloader::path";

    /**
     *
     * @var array
     */
    public static $human_names = array(
        self::zesk_version_release => 'Zesk Version',
        self::zesk_version_string => 'Zesk Version String',
        self::zesk_application_theme_path => 'Application Theme Path',
        self::zesk_application_root => 'Zesk Application Root',
        self::zesk_root => 'Zesk Root',
        'enable_dl' => 'Enable Dynamic Libraries',
        'php_ini' => 'php.ini Path',
        self::command_path => 'Shell Command Path',
        'zesk_command_path' => 'Zesk Command Path',
        self::zesk_autoload_path => 'Zesk Autoload Path',
        'display_startup_errors' => 'Display Startup Errors',
        'error_log' => 'PHP Error Log',
        self::zesk_application_class => 'Zesk Application Class',
        self::configuration_files_loaded => 'Loaded Configuration Files',
    );

    /**
     *
     * {@inheritdoc}
     *
     * @see Command::run()
     */
    public function run() {
        $app = $this->application;

        $info[self::zesk_version_release] = Version::release();
        $info[self::zesk_version_string] = Version::string();
        $info[self::zesk_root] = ZESK_ROOT;
        $info[self::zesk_application_root] = $app->path();
        $info[self::zesk_application_class] = $app->application_class();
        $info[self::command_path] = $app->command_path();
        $info[self::zesk_application_theme_path] = $app->theme_path();
        $info['zesk_command_path'] = $app->zesk_command_path();
        $info[self::zesk_autoload_path] = $app->autoloader->path();
        $info['enable_dl'] = ini_get('enable_dl') ? 'true' : 'false';
        $info['php_ini'] = get_cfg_var('cfg_file_path');
        $info['display_startup_errors'] = to_bool(ini_get('display_startup_errors')) ? 'true' : 'false';
        $info['error_log'] = ini_get('error_log');
        $info[self::configuration_files_loaded] = to_array(avalue($app->loader->variables(), 'processed', array()));

        $module_info = $app->modules->all_hook_arguments("info", array(
            array(),
        ), array());
        $info = array_merge($info, ArrayTools::key_value($module_info, null, "value"));
        $human_names = array();
        foreach ($module_info as $code_name => $settings) {
            $human_names[$code_name] = avalue($settings, "title", $code_name);
        }

        if (!$this->option_bool('computer-labels')) {
            $info = ArrayTools::map_keys($info, $human_names + self::$human_names);
        }
        $this->render_format($info, $this->option("format"));
        return 0;
    }
}
