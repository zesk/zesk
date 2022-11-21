<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * This help.
 *
 * @category Documentation
 */
class Command_Help extends Command_Base {
	protected array $option_types = [
		'no-core' => 'boolean',
	];

	protected array $option_help = [
		'no-core' => 'Skip all Zesk core commands',
	];

	/**
	 *
	 * @var array
	 */
	private array $categories = [];

	/**
	 *
	 * @var array
	 */
	private array $aliases = [];

	private array $command_paths = [];

	public function run() {
		$this->collect_help();
		echo $this->application->theme('command/help', [
			'categories' => $this->categories,
			'aliases' => $this->aliases,
		]);
		$this->save_aliases($this->aliases);
		return 0;
	}

	/**
	 */
	public function collect_command_files() {
		$zesk = $this->zesk;
		$command_files = [];
		$opts = [
			'rules_file' => [
				'/.*\.(inc|php)$/' => true,
				false,
			],
			'rules_directory_walk' => [
				'#/\..*$#' => false,
				true,
			],
			'rules_directory' => false,
		];
		$zesk_root = $this->application->zeskHome();
		$nocore = $this->optionBool('no-core');
		foreach ($this->application->zesk_command_path() as $path => $prefix) {
			$this->command_paths[] = $path;
			if ($nocore && begins($path, $zesk_root)) {
				continue;
			}
			$commands = Directory::list_recursive($path, $opts);
			if ($commands) {
				$command_files[$path] = [
					$prefix,
					ArrayTools::ltrim($commands, './'),
				];
			}
		}
		return $command_files;
	}

	public function load_commands(array $command_files): void {
		$declared_classes_before = ArrayTools::keysFromValues(get_declared_classes(), true);
		foreach ($command_files as $path => $structure) {
			[$prefix, $commands] = $structure;
			foreach ($commands as $command) {
				$this->verbose_log('Scanning {command}', compact('command'));
				$command_file = path($path, $command);
				if (strcasecmp($command_file, __FILE__) === 0) {
					continue;
				}

				try {
					$this->verbose_log("Including $command_file");
					require_once $command_file;
				} catch (\Exception $e) {
					$this->error('Error processing {command_file}: {exception}', [
						'exception' => $e->getMessage(),
						'command_file' => $command_file,
					]);
				}
			}
		}
		$declared_classes_after = get_declared_classes();
		foreach ($declared_classes_after as $class) {
			if (array_key_exists($class, $declared_classes_before)) {
				continue;
			}
			$this->verbose_log("Registering $class");
			$this->application->classes->register($class);
		}
	}

	public function process_class($class): void {
		$this->verbose_log("Checking $class");

		try {
			$reflection_class = new \ReflectionClass($class);
		} catch (Exception_Class_NotFound $e) {
			$this->verbose_log('{class} can not be loaded, skipping', [
				'class' => $class,
			]);
			return;
		}
		if ($reflection_class->isAbstract()) {
			$this->verbose_log('{class} is abstract, skipping', [
				'class' => $class,
			]);
			return;
		}
		$command_file = $reflection_class->getFileName();
		$command = StringTools::removePrefix($command_file, $this->command_paths);
		$command = File::extension_change(ltrim($command, '/'), null);
		$command = strtr($command, '/', '-');
		$docComment = $reflection_class->getDocComment();
		$docComment = DocComment::instance($docComment)->variables();
		if (array_key_exists('ignore', $docComment)) {
			return;
		}
		if (array_key_exists('aliases', $docComment)) {
			foreach (to_list($docComment['aliases'], [], ' ') as $alias) {
				if (array_key_exists($alias, $this->aliases)) {
					$this->application->logger->warning('Identical aliases exist for command {0} and {1}: {2}, only {0} will be honored', [
						$command,
						$this->aliases[$alias],
						$alias,
					]);
				} else {
					$this->aliases[$alias] = $command;
					$this->verbose_log('Alias for `zesk {command}` is `zesk {alias}`', [
						'command' => $command,
						'alias' => $alias,
					]);
				}
			}
		}
		$docComment['command'] = $command;
		$docComment['command_file'] = $command_file;
		$category = $docComment['category'] ?? 'Miscellaneous';
		$this->categories[$category][$command] = $docComment;
	}

	public function collect_help(): void {
		$command_files = $this->collect_command_files();

		$this->load_commands($command_files);

		$this->aliases = [];
		$this->categories = [];

		$subclasses = $this->application->classes->subclasses("zesk\Command");
		foreach ($subclasses as $subclass) {
			$this->process_class($subclass);
		}

		ksort($this->categories);
	}

	public function save_aliases(array $aliases): bool {
		$paths = $this->application->configureInclude();
		foreach ($paths as $index => $confpath) {
			$paths[$index] = dirname($confpath);
		}
		$name = 'command-aliases.json'; // Put this in a single location
		$content = JSON::encodePretty($aliases);
		$conf_file = File::find_first($paths, $name);
		if ($conf_file) {
			if (file_get_contents($conf_file) === $content) {
				return false;
			}

			try {
				File::put($conf_file, $content);
				$this->application->logger->notice('Wrote {file}', [
					'file' => $conf_file,
				]);
				return true;
			} catch (\Exception $e) {
				echo $e::class . ' ' . $e->getMessage();
			}
		}
		while (count($paths) > 0) {
			$path = array_pop($paths);

			try {
				$conf_file = path($path, $name);
				File::put($conf_file, $content);
				$this->application->logger->notice('Wrote {file}', [
					'file' => $conf_file,
				]);
				return true;
			} catch (\Exception $e) {
				echo $e::class . ' ' . $e->getMessage();
			}
		}
		return false;
	}
}
