<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use zesk\Adapter\SettingsArray;
use zesk\Configuration\Parser;
use zesk\Configure\Engine;
use zesk\Doctrine\Command\Command;
use zesk\Exception\ClassNotFound;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\ParseException;

/**
 * Automatically keep a series of files and directories in sync between users, with security checks
 * for superusers, and simplify version control of remote systems.
 *
 * Basically, if you deploy software to remote systems, this lets you keep your configuration files
 * in a source repository and copy them into the
 * appropriate locations without too much extra work.
 *
 * The configure command is intended to run as a mini-Zesk application and will likely include PHP
 * configuration scripts in the future.
 *
 * @alias sync
 * @see \zesk\Configure\Engine
 * @author kent
 * @category Management
 */
class Command_Configure extends SimpleCommand {
	protected array $shortcuts = ['configure'];

	/**
	 * Append to a command to redirect stderr to stdout
	 * @var string
	 */
	public const STDERR_REDIRECT = ' 2>&1';

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'non-interactive' => 'boolean',
		'environment-file' => 'string',
		'host-setting-name' => 'string',
	];

	protected array $option_chars = [
		'y' => 'non-interactive',
	];

	/**
	 * owner - Set owner of the file (root only)
	 * mode - Set the mode of the file (root only)
	 * map - Map variables in the file using our environment before copying
	 *
	 * @var array
	 */
	protected static $file_flags = [
		'owner' => true,
		'mode' => true,
		'map' => true,
		'trim' => true,
	];

	/**
	 *
	 * @var Engine
	 */
	private Engine $engine;

	/**
	 * Whether the configuration should be saved
	 *
	 * @var boolean
	 */
	private bool $need_save;

	/**
	 *
	 * @var string
	 */
	private string $host_path;

	/**
	 *
	 * @var string
	 */
	private string $username = '';

	/**
	 * List of known host configurations
	 *
	 * @var array
	 */
	private array $possible_host_configurations = [];

	/**
	 * List of host configurations
	 *
	 * @var array
	 */
	private array $host_configurations = [];

	protected string $host_setting_name;

	/**
	 *
	 */
	protected function run(): int {
		$this->setCompletionFunction();

		$this->configure('configure', true);

		if ($this->option('debug')) {
			$this->application->process->debug = true;
		}
		$this->engine = new Engine($this->application, $this->options());
		$this->engine->setPrompt($this);
		$this->engine->setLogger($this);

		$this->log('Configuration synchronization for: {uname}, user: {user}', $this->engine->variable_map());
		$this->determineEnvironmentFiles();
		if (!$this->determine_host_path_setting_name()) {
			return 1;
		}
		$this->determine_host_name();

		$this->save_configuration_changes();

		$this->debugLog('Variables: {variables}', [
			'variables' => Text::formatPairs($this->engine->variable_map()),
		]);
		if (!$this->configure_user()) {
			return 99;
		}
		$this->verboseLog('Success');
		return 0;
	}

	/**
	 * If the environment_file option is not set, interactively set it
	 *
	 * @return string
	 * @throws DirectoryNotFound
	 * @throws Redirect
	 * @throws Semantics
	 */
	private function determineEnvironmentFile(): string {
		$locale = $this->application->locale;
		$value = $this->optionString('environment_file');
		$times = 0;
		$this->completions = Directory::ls('/etc/', '/(.conf|.sh|.json)$/', true);
		while (empty($value) || !is_file($value)) {
			if ($times > 2) {
				echo $locale->__("System settings file is a BASH and Zesk Configuration_Loader parsable file which contains a global which points to this host's configuration directory.\n\n");
			}
			$value = trim($this->prompt($locale->__('Path to system settings file: ')));
			++$times;
			$this->need_save = true;
		}
		$this->engine->variable_map(self::OPTION_ENVIRONMENT_FILE, $value);
		$this->setOption(self::OPTION_ENVIRONMENT_FILE, $value);
		return $value;
	}

	public const OPTION_ENVIRONMENT_FILE = 'environment_file';

	public const OPTION_ENVIRONMENT_FILES = 'environment_files';

	/**
	 * Determine the environment files for configuration
	 *
	 * @return string[]
	 * @throws DirectoryNotFound
	 * @throws Redirect
	 * @throws Semantics
	 */
	private function determineEnvironmentFiles():array {
		$files = $this->optionIterable(self::OPTION_ENVIRONMENT_FILES);
		if (count($files) === 0) {
			$file = $this->determineEnvironmentFile();
			if (file_exists($app_file = $this->application->path($file))) {
				$files[] = $app_file;
			}
			$this->setOption(self::OPTION_ENVIRONMENT_FILES, $files);
		}
		return $files;
	}

	/**
	 * If the host_setting_name option is not set, interactively set it
	 *
	 * @return null|string
	 */
	private function determine_host_path_setting_name(): null|string {
		$locale = $this->application->locale;
		$value = $this->host_setting_name;
		$times = 0;
		$output = false;
		while (is_array($dirs = $this->load_dirs($output)) && !array_key_exists($value, $dirs)) {
			if (count($dirs) === 0) {
				echo $locale->__('No possible directory settings in {environment_file}, please edit and add variable which points to a local directory with host information', $this->options());
			}
			if ($times > 2) {
				echo $locale->__("The system setting will point to a directory of host configurations to keep in sync.\n\n");
			}
			$this->completions = array_keys($dirs);
			$value = trim($this->prompt($locale->__('Name of system setting: ')));
			++$times;
			$this->need_save = true;
			$output = true;
		}
		$this->host_setting_name = $value;
		$this->host_path = $dirs[$value];
		$this->engine->variable_map('host_path', $this->host_path);
		if (!is_dir($this->host_path)) {
			$this->error('Host path does not exist? {host_path}', [
				'host_path' => $this->host_path,
			]);
			return null;
		}
		return $value;
	}

	/**
	 * Load a configuration file and return the loaded configuration as an array
	 *
	 * @param string $path
	 * @param string $extension
	 * @return array
	 * @throws ClassNotFound
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws ParseException
	 */
	private function load_conf(string $path, string $extension = ''): array {
		$conf = [];
		Parser::factory($extension ?: File::extension($path), File::contents($path), new SettingsArray($conf), [
			'lower' => false,
		])->process();
		return $conf;
	}

	/**
	 * Write out a configuration file to path
	 *
	 * @param string $path
	 * @param array $settings
	 * @return boolean
	 */
	private function save_conf(string $path, array $settings): void {
		$conf = [];
		$contents = File::contents($path);
		$parser = Configuration_Parser::factory(File::extension($path), $contents, new SettingsArray($conf));
		$editor = $parser->editor($contents);
		File::put($path, $editor->edit($settings));
	}

	/**
	 * Fetch our environment file and determine which entries point to directories on this system
	 *
	 * @param bool $output
	 * @return array
	 */
	private function load_dirs(bool $output = false): array {
		$locale = $this->application->locale;

		$this->verboseLog('Loading {environment_files}', [
			'environment_files' => $this->optionIterable(self::OPTION_ENVIRONMENT_FILES),
		]);
		$env = [];
		foreach ($this->optionIterable(self::OPTION_ENVIRONMENT_FILES) as $environment_file) {
			$env += $this->load_conf($environment_file, File::extension($environment_file) === 'sh' ? 'conf' : null);
		}
		$this->engine->variable_map(array_change_key_case($env));
		$dirs = [];
		foreach ($env as $name => $value) {
			if (is_string($value) && (str_starts_with($value, '/') || str_starts_with($value, '.')) && is_dir($value)) {
				$dirs[$name] = $value;
			} else {
				$possibilities[] = $name;
			}
		}
		if ($output) {
			$this->log($locale->__('Non-directory settings: {possibilities}', [
				'possibilities' => implode(' ', $possibilities),
			]));
			$this->log($locale->__('Available settings: {dirs}', [
				'dirs' => implode(' ', array_keys($dirs)),
			]));
		}
		return $dirs;
	}

	/**
	 * Determine host path (an ordered list of strings) to traverse when finding inherited files
	 *
	 * @return mixed|array
	 */
	private function determine_host_name() {
		$locale = $this->application->locale;

		$this->possible_host_configurations = ArrayTools::valuesRemoveSuffix(Directory::ls($this->host_path), '/', true);
		$alias_file = path($this->host_path, 'aliases.conf');
		$__ = [
			'alias_file' => $alias_file,
		];
		$this->verboseLog('Alias file is {alias_file}', $__);
		$uname = $this->engine->variable_map('uname');
		if (!is_file($alias_file)) {
			self::file_put_contents_inherit($alias_file, "$uname=[]");
			$this->verboseLog('Created empty {alias_file}', $__);
		}
		while (!is_array($host_configs = (($aliases = $this->load_conf($alias_file))[strtolower($uname)] ?? null)) || count(array_diff(
			$host_configs,
			$this->possible_host_configurations
		)) !== 0) {
			$configs = $this->determine_host_configurations();
			if ($this->promptYesNo($locale->__("Save changes to {alias_file} for $uname:{uname}? ", $__ + $this->engine->variable_map()))) {
				$this->save_conf($alias_file, [
					$uname => $configs,
				]);
				$this->log('Changed {aliasFile} ({aliases})', [
					'aliasFile' => $alias_file,
					'aliases' => $aliases,
				]);
			}
		}
		$this->host_configurations = $host_configs;
		return $host_configs;
	}

	/**
	 * Interactively request a list of host configurations
	 *
	 * @return array
	 */
	private function determine_host_configurations() {
		$locale = $this->application->locale;

		$this->completions = $possible_host_configurations = $this->possible_host_configurations;
		do {
			$message = $locale->__('Host configurations: {configs}', [
				'configs' => implode(' ', $possible_host_configurations),
			]) . "\n\n";
			$message .= $locale->__('Enter a list of configurations separated by space') . "\n";
			$host_configurations = $this->prompt("$message\n> ");
			$host_configurations = explode(' ', preg_replace("/\s+/", ' ', trim($host_configurations)));
			$diff = array_diff($host_configurations, $possible_host_configurations);
		} while (count($diff) !== 0);
		$this->log('Will add host configuration for host {host}: {host_configurations}', [
			'host' => $this->host,
			'host_configurations' => implode(' ', $host_configurations),
		]);
		return $host_configurations;
	}

	/**
	 * Save configuration changes to the configuration file associated with this command
	 */
	private function save_configuration_changes(): void {
		if ($this->need_save) {
			$__ = [
				'config' => $this->config,
			];
			$locale = $this->application->locale;
			if ($this->promptYesNo($locale->__('Save changes to {config}? ', $__))) {
				$this->save_conf($this->config, ArrayTools::prefixKeys($this->options(['environment_file', 'host_setting_name']), __CLASS__ . '::'));
				$this->log('Wrote {config}', $__);
			}
		}
	}

	/**
	 * Configure particular user
	 *
	 * @return boolean
	 */
	private function configure_user() {
		$locale = $this->application->locale;

		$username = $this->username;
		$paths = [];
		foreach ($this->host_configurations as $host) {
			$paths[] = path($this->host_path, $host);
		}
		$this->verboseLog($locale->__("Configuration paths:\n\t{paths}", [
			'paths' => implode("\n\t", $paths),
		]));
		$this->engine->host_paths($paths);

		$pattern = $this->option('user_configuration_file', 'users/{user}/configure');
		$suffix = $this->engine->map($pattern);
		$files = File::findAll($paths, $suffix);
		$this->log($locale->__("Configuration files:\n\t{files}", [
			'files' => implode("\n\t", $files),
		]));

		foreach ($files as $file) {
			$this->engine->variable_map([
				'current_host_path' => rtrim(StringTools::removeSuffix($file, $suffix), '/'),
				'self_path' => dirname($file),
				'self' => $file,
			]);
			$this->verboseLog('Processing file {file}', compact('file'));
			$contents = File::contents($file);
			$contents = Text::removeLineComments($contents, '#', false);
			$lines = ArrayTools::listTrimClean(explode("\n", $contents));
			foreach ($lines as $line) {
				if (!$this->engine->process($line)) {
					return false;
				}
			}
		}
		$this->engine->variable_map([
			'current_host_path' => null,
			'self_path' => null,
			'self' => null,
		]);
		return true;
	}
}
