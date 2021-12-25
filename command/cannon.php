<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Global search and replace tool for code
 *
 * @category Tools
 */
class Command_Cannon extends Command_Base {
	protected array $option_types = [
		"directory" => 'dir',
		"dir" => 'dir',
		"list" => 'boolean',
		"show" => 'boolean',
		"dry-run" => 'boolean',
		"backup" => 'boolean',
		"duplicate" => 'boolean',
		"config" => 'file',
		"extensions" => 'string',
		"extension" => 'string',
		"files" => "files",
		"skip-when-matches" => "string[]",
		"also-match" => "string[]",
		"*" => "string",
	];

	protected array $option_help = [
		"directory" => 'Directory to look for files',
		"dir" => 'Synonym for --directory',
		"list" => 'List files which would be scanned',
		"show" => 'Output files and matched lines',
		"dry-run" => 'Show what files would match without changing anythiing (implies --show)',
		"backup" => 'Backup files before changing',
		"duplicate" => 'Create a copy of the file next to the original which has changes',
		"extensions" => "List of extensions separated by commas to look for",
		"extension" => 'Synonym for --extensions',
		"files" => "Just run against this file",
		"skip-when-matches" => "Skip replacements in files which contain ANY of the string(s) specified.",
		"also-match" => "File must also match ANY of the given strings(s) in addition to the search term",
		"*" => "Follow by Search string and Replace string",
	];

	protected array $options = [
		'max_file_size' => 8388608,
	];

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public $has_configuration = true;

	/**
	 * @var array
	 */
	private $skip_when_matches = null;

	/**
	 *
	 */
	private $also_match = null;

	/**
	 *
	 */
	public function run(): void {
		$this->configure('cannon');

		$dir = $this->first_option("dir;directory");
		if ($dir && !is_dir($dir)) {
			$this->usage("$dir is not a directory");
		}
		$list = $this->option_bool('list');
		$backup = $this->option_bool('backup');
		$duplicate = $this->option_bool('duplicate');
		$show = $this->option_bool('show');

		$this->verbose_log("Verbose enabled.");
		if ($this->option_bool('dry-run')) {
			$this->verbose_log("Dry run - nothing will change.");
		}
		if ($dir === null && $this->has_option("files")) {
			$files = $this->option("files");
		} else {
			if ($dir === null) {
				$dir = getcwd();
			}
			$extensions = $this->first_option("extensions;extension", "php|inc|php4|php5|tpl|html|htm|sql|phpt|module|install|conf|md|markdown|css|less|js");
			$extensions = explode(",", strtr($extensions, [
				"|" => ",",
				"." => "",
				";" => ",",
			]));
			$this->verbose_log("Generating file list ...");
			$files = $this->_list_files($dir, $extensions);
			$this->verbose_log("{count} files found", [
				"count" => count($files),
			]);
		}
		if ($list) {
			echo implode("\n", $files) . "\n";
			return;
		}
		$this->skip_when_matches = $this->option_array("skip-when-matches");
		if (count($this->skip_when_matches) === 0) {
			$this->skip_when_matches = null;
		} else {
			$this->verbose_log("Skipping files which contain: \n\t\"" . implode("\"\n\t\"", $this->skip_when_matches) . "\"\n\n");
			$stats['skipped'] = 0;
		}
		$this->also_match = $this->option_array("also-match");
		if (count($this->also_match) === 0) {
			$this->also_match = null;
		} else {
			$this->verbose_log("Replacement files MUST contain one of: \n\t\"" . implode("\"\n\t\"", $this->also_match) . "\"\n\n");
			$stats['skipped'] = 0;
		}

		if ($this->has_arg()) {
			$search = $this->get_arg("search");
		} else {
			echo " Search? ";
			$search = rtrim(fgets(STDIN), "\n\r");
		}
		$replace = null;
		if ($this->has_arg()) {
			$replace = $this->get_arg("replace");
		} elseif (!$show) {
			echo "Replace? ";
			$replace = rtrim(fgets(STDIN), "\n\r");
		}
		if (empty($search)) {
			$this->usage("Must have a non-blank search phrase.");
		}
		if ($show) {
			$this->verbose_log("Showing matches only");
		} elseif ($backup) {
			if ($duplicate) {
				$this->error("--duplicate and --backup are exclusive, ignoring --backup");
			}
			$this->verbose_log("Backing up files with matches");
		}
		$locale = $this->application->locale;
		$this->log(" Search: $search (" . $locale->plural_word("character", strlen($search)) . ")");
		$this->log("Replace: $replace (" . $locale->plural_word("character", strlen($replace)) . ")");
		$stats = [
			'files' => 0,
			'lines' => 0,
		];
		foreach ($files as $file) {
			$result = $this->_replace_file($file, $search, $replace);
			if ($result > 0) {
				$stats['files']++;
				$stats['lines'] += $result;
			} elseif ($result < 0) {
				if (!isset($stats['skipped'])) {
					$stats['skipped'] = 0;
				}
				$stats['skipped']++;
			}
		}
		$this->log(Text::format_pairs($stats));
	}

	/**
	 * List files
	 */
	private function _list_files($dir, array $extensions) {
		$options = [];
		$options['rules_file'] = [
			'#\.' . implode("|", $extensions) . '$#' => true,
			'#.*/\.#' => true,
			false,
		];
		$options['rules_directory_walk'] = [
			'#.*/\.#' => false,
			true,
		];
		$options['rules_directory'] = [
			false,
		];
		$options['add_path'] = true;

		return Directory::list_recursive($dir, $options);
	}

	private function _replace_file($file, $search, $replace) {
		if (($size = filesize($file)) > $this->option_integer("max_file_size")) {
			$this->log("Skipping {size} {file}", [
				"size" => Number::format_bytes($this->application->locale, $size),
				"file" => $file,
			]);
			return 0;
		}
		$backup = $this->option_bool('backup');
		$duplicate = $this->option_bool('duplicate');
		$show = $this->option_bool('show');
		$dry_run = $this->option_bool('dry-run');
		$contents = file_get_contents($file);
		if (!str_contains($contents, $search)) {
			$this->debug_log("$file: No matches");
			return 0;
		}
		if (is_array($this->skip_when_matches) && StringTools::contains($contents, $this->skip_when_matches)) {
			return -1;
		}

		if (is_array($this->also_match) && !StringTools::contains($contents, $this->also_match)) {
			return -1;
		}

		$rabbit = "\x01";
		$search_tr = [
			$search => $rabbit,
		];
		$replace_tr = [
			$rabbit => $replace,
		];

		$lines = explode("\n", strtr($contents, $search_tr));
		foreach ($lines as $lineno => $line) {
			if (!str_contains($line, $rabbit)) {
				unset($lines[$lineno]);
			}
		}
		$locale = $this->application->locale;
		if ($dry_run || $show) {
			echo "$file: " . $locale->plural_word("match", count($lines)) . "\n";
			$carrots_tr = [
				$rabbit => str_repeat("^", strlen($replace)),
			];
			foreach ($lines as $lineno => $line) {
				$line = strtr($line, [
					"\t" => "    ",
				]);
				echo Text::ralign($lineno + 1, 4) . ": " . strtr($line, [
					$rabbit => $search,
				]) . "\n";
				echo Text::ralign($lineno + 1, 4) . ": " . strtr($line, $replace_tr) . "\n";
				$carrot_line = preg_replace("#[^$rabbit]#", " ", $line);
				echo Text::ralign("", 4) . "  " . strtr($carrot_line, $carrots_tr) . "\n";
			}
			return count($lines);
		}
		if ($duplicate) {
			$ext = File::extension($file);
			$dupfile = File::extension_change($file, ".cannon.$ext");
			$this->verbose_log("Writing $dupfile: " . $locale->plural_word("change", count($lines)));
			file_put_contents($dupfile, strtr($contents, [
				$search => $replace,
			]));
		} else {
			if ($backup) {
				File::rotate($file, null, 3, ".old");
			}
			file_put_contents($file, strtr($contents, [
				$search => $replace,
			]));
		}
		return count($lines);
	}
}
