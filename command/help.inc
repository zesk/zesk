<?php
/**
 * This help.
 * @category Documentation
 */
class Command_Help extends Command_Base {

	protected $option_types = array(
		'no-core' => 'boolean'
	);
	protected $option_help = array(
		'no-core' => 'Skip all Zesk core commands'
	);

	function run() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$command_files = array();
		$opts = array(
			'file_default' => false,
			'file_include_pattern' => '/.*\.inc$/',
			'directory_default' => false,
			'directory_walk_exclude_pattern' => '#/\..*$#'
		);
		foreach (zesk::zesk_command_path() as $path) {
			if ($this->option_bool("no-core") && begins($path, zesk::root())) {
				continue;
			}
			$commands = dir::list_recursive($path, $opts);
			if ($commands) {
				$command_files[$path] = $commands;
			}
		}
		$aliases = array();
		$categories = array();
		foreach ($command_files as $path => $commands) {
			foreach ($commands as $command) {
				$command_file = path($path, $command);
				if ($zesk->configuration->debug) {
					echo "Checking $command_file\n";
				}
				$command = basename(strtr($command, array(
					"/" => "-"
				)), ".inc");
				$doccomments = doccomment::extract(file_get_contents($command_file));
				$doccomment = null;
				if (count($doccomments) > 0) {
					$doccomment = doccomment::parse($doccomments[0]);
				}
				if (!is_array($doccomment)) {
					$doccomment = array();
				}
				if (array_key_exists('ignore', $doccomment)) {
					continue;
				}
				if (array_key_exists('aliases', $doccomment)) {
					foreach (to_list($doccomment['aliases'], array(), " ") as $alias) {
						if (array_key_exists($alias, $aliases)) {
							log::warning("Identical aliases exist for command {0} and {1}: {2}, only {0} will be honored", array(
								$command,
								$aliases[$alias],
								$alias
							));
						} else {
							$aliases[$alias] = $command;
						}
					}
				}
				$doccomment['command'] = $command;
				$doccomment['command_file'] = $command_file;
				$category = avalue($doccomment, 'category', 'Miscellaneous');
				$categories[$category][$command] = $doccomment;
			}
		}
		ksort($categories);
		echo $this->application->theme("command/help", array(
			"categories" => $categories,
			"aliases" => $aliases
		));
		$this->save_aliases($aliases);
	}

	function save_aliases(array $aliases) {
		$paths = Application::instance()->configuration_path();
		$name = "command-aliases.conf"; // Put this in a single location
		$conf_file = zesk::find_file($paths, $name);
		if ($conf_file) {
			try {
				//echo "WRITING $conf_file\n";
				conf::edit($conf_file, $aliases);
				//echo "WROTE $conf_file\n";
				return true;
			} catch (Exception $e) {
				echo get_class($e) . " " . $e->getMessage();
			}
		}
		while (count($paths) > 0) {
			$path = array_pop($paths);
			try {
				$conf_file = path($path, $name);
				echo "WRITING $conf_file\n";
				conf::edit($conf_file, $aliases);
				echo "WROTE $conf_file\n";
				log::notice("Wrote {file}", array(
					"file"
				));
				return true;
			} catch (Exception $e) {
				echo get_class($e) . " " . $e->getMessage();
			}
		}
		return false;
	}
}