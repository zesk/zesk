<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @copyright @copy; 2023 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Global search and replace tool for code
 *
 * @category Tools
 */
class Command_Cannon extends Command_Base {
	protected array $shortcuts = ['cannon'];

	protected array $option_types = [
		'directory' => 'dir',
		'dir' => 'dir',
		'list' => 'boolean',
		'show' => 'boolean',
		'dry-run' => 'boolean',
		'backup' => 'boolean',
		'duplicate' => 'boolean',
		'config' => 'file',
		'extensions' => 'string',
		'extension' => 'string',
		'files' => 'files',
		'skip-when-matches' => 'string[]',
		'also-match' => 'string[]',
		'*' => 'string',
	];

	protected array $option_help = [
		'directory' => 'Directory to look for files',
		'dir' => 'Synonym for --directory',
		'list' => 'List files which would be scanned',
		'show' => 'Output files and matched lines',
		'dry-run' => 'Show what files would match without changing anythiing (implies --show)',
		'backup' => 'Backup files before changing',
		'duplicate' => 'Create a copy of the file next to the original which has changes',
		'extensions' => 'List of extensions separated by commas to look for',
		'extension' => 'Synonym for --extensions',
		'files' => 'Just run against this file',
		'skip-when-matches' => 'Skip replacements in files which contain ANY of the string(s) specified.',
		'also-match' => 'File must also match ANY of the given strings(s) in addition to the search term',
		'*' => 'Follow by Search string and Replace string',
	];

	protected array $options = [
		'max_file_size' => 8388608,
	];

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public bool $has_configuration = true;

	/**
	 * @var ?array
	 */
	private ?array $skip_when_matches = null;

	/**
	 * @var ?array
	 */
	private ?array $also_match = null;

	/**
	 *
	 */
	public function run(): int {
		$this->configure('cannon');

		$dir = $this->firstOption(['dir', 'directory']);
		if ($dir && !is_dir($dir)) {
			$this->usage("$dir is not a directory");
		}
		$list = $this->optionBool('list');
		$backup = $this->optionBool('backup');
		$duplicate = $this->optionBool('duplicate');
		$show = $this->optionBool('show');

		$this->verboseLog('Verbose enabled.');
		if ($this->optionBool('dry-run')) {
			$this->verboseLog('Dry run - nothing will change.');
		}
		if ($dir === null && $this->hasOption('files')) {
			$files = $this->option('files');
		} else {
			if ($dir === null) {
				$dir = getcwd();
			}
			$extensions = $this->firstOption(
				['extensions', 'extension'],
				'php|inc|php4|php5|tpl|html|htm|sql|phpt|module|install|conf|md|markdown|css|less|js'
			);
			$extensions = explode(',', strtr($extensions, [
				'|' => ',',
				'.' => '',
				';' => ',',
			]));
			$this->verboseLog('Generating file list ...');
			$files = $this->_listFiles($dir, $extensions);
			$this->verboseLog('{count} files found', [
				'count' => count($files),
			]);
		}
		if ($list) {
			echo implode("\n", $files) . "\n";
			return 0;
		}
		$this->skip_when_matches = $this->optionArray('skip-when-matches');
		if (count($this->skip_when_matches) === 0) {
			$this->skip_when_matches = null;
		} else {
			$this->verboseLog("Skipping files which contain: \n\t\"" . implode("\"\n\t\"", $this->skip_when_matches) . "\"\n\n");
			$stats['skipped'] = 0;
		}
		$this->also_match = $this->optionArray('also-match');
		if (count($this->also_match) === 0) {
			$this->also_match = null;
		} else {
			$this->verboseLog("Replacement files MUST contain one of: \n\t\"" . implode("\"\n\t\"", $this->also_match) . "\"\n\n");
			$stats['skipped'] = 0;
		}

		if ($this->hasArgument()) {
			$search = $this->getArgument('search');
		} else {
			echo ' Search? ';
			$search = rtrim(fgets(STDIN), "\n\r");
		}
		$replace = null;
		if ($this->hasArgument()) {
			$replace = $this->getArgument('replace');
		} elseif (!$show) {
			echo 'Replace? ';
			$replace = rtrim(fgets(STDIN), "\n\r");
		}
		if (empty($search)) {
			$this->usage('Must have a non-blank search phrase.');
		}
		if ($show) {
			$this->verboseLog('Showing matches only');
		} elseif ($backup) {
			if ($duplicate) {
				$this->error('--duplicate and --backup are exclusive, ignoring --backup');
			}
			$this->verboseLog('Backing up files with matches');
		}
		$locale = $this->application->locale;
		$this->log(" Search: $search (" . $locale->plural_word('character', strlen($search)) . ')');
		$this->log("Replace: $replace (" . $locale->plural_word('character', strlen($replace)) . ')');
		$stats = [
			'files' => 0,
			'lines' => 0,
		];
		foreach ($files as $file) {
			$result = $this->_replaceFile($file, $search, $replace);
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
		$this->log(Text::formatPairs($stats));
		return 0;
	}

	/**
	 * List files
	 */
	private function _listFiles($dir, array $extensions): array {
		$options = [];
		$options[Directory::LIST_RULE_FILE] = [
			'#\.' . implode('|', $extensions) . '$#' => true,
			'#.*/\.#' => true,
			false,
		];
		$options[Directory::LIST_RULE_DIRECTORY_WALK] = [
			'#.*/\.#' => false,
			true,
		];
		$options[Directory::LIST_RULE_DIRECTORY] = [
			false,
		];
		$options[Directory::LIST_ADD_PATH] = true;

		return Directory::listRecursive($dir, $options);
	}

	/**
	 * @param string $file
	 * @param string $search
	 * @param string $replace
	 * @return int
	 */
	private function _replaceFile(string $file, string $search, string $replace): int {
		if (($size = filesize($file)) > $this->optionInt('max_file_size')) {
			$this->log('Skipping {size} {file}', [
				'size' => Number::formatBytes($this->application->locale, $size),
				'file' => $file,
			]);
			return 0;
		}
		$backup = $this->optionBool('backup');
		$duplicate = $this->optionBool('duplicate');
		$show = $this->optionBool('show');
		$dry_run = $this->optionBool('dry-run');
		$contents = file_get_contents($file);
		if (!str_contains($contents, $search)) {
			$this->debugLog("$file: No matches");
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
		$sameLength = strlen($search) === strlen($replace);
		$lines = explode("\n", strtr($contents, $search_tr));
		foreach ($lines as $lineno => $line) {
			if (!str_contains($line, $rabbit)) {
				unset($lines[$lineno]);
			}
		}
		$locale = $this->application->locale;
		if ($dry_run || $show) {
			echo "$file: " . $locale->plural_word('match', count($lines)) . "\n";
			$carrotSearch = [
				$rabbit => str_repeat('^', strlen($search)),
			];
			$carrotReplace = [
				$rabbit => str_repeat('^', strlen($replace)),
			];
			foreach ($lines as $lineno => $line) {
				$line = strtr($line, [
					"\t" => '    ',
				]);
				$carrot_line = preg_replace("#[^$rabbit]#", ' ', $line);

				echo Text::rightAlign(strval($lineno + 1), 4) . ': ' . strtr($line, [
					$rabbit => $search,
				]) . "\n";
				if (!$sameLength) {
					echo Text::rightAlign('', 4) . '  ' . strtr($carrot_line, $carrotSearch) . "\n";
				}
				echo Text::rightAlign(strval($lineno + 1), 4) . ': ' . strtr($line, $replace_tr) . "\n";
				echo Text::rightAlign('', 4) . '  ' . strtr($carrot_line, $carrotReplace) . "\n";
			}
			return count($lines);
		}
		if ($duplicate) {
			$ext = File::extension($file);
			$dupfile = File::setExtension($file, ".cannon.$ext");
			$this->verboseLog("Writing $dupfile: " . $locale->plural_word('change', count($lines)));
			file_put_contents($dupfile, strtr($contents, [
				$search => $replace,
			]));
		} else {
			if ($backup) {
				File::rotate($file, 0, 3, '.old');
			}
			file_put_contents($file, strtr($contents, [
				$search => $replace,
			]));
		}
		return count($lines);
	}
}
