<?php

/**
 * Global search and replace tool for code
 *
 * @category Tools
 */
class Command_Cannon extends Command_Base {
	protected $option_types = array(
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
		"*" => "string"
	);
	protected $option_help = array(
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
		"*" => "Follow by Search string and Replace string"
	);
	function run() {
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
			$extensions = explode(",", strtr($extensions, array(
				"|" => ",",
				"." => "",
				";" => ","
			)));
			$files = $this->_list_files($dir, $extensions);
		}
		if ($list) {
			echo implode("\n", $files) . "\n";
			return;
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
		} else if (!$show) {
			echo "Replace? ";
			$replace = rtrim(fgets(STDIN), "\n\r");
		}
		if (empty($search)) {
			$this->usage("Must have a non-blank search phrase.");
		}
		if ($show) {
			$this->verbose_log("Showing matches only");
		} else if ($backup) {
			if ($duplicate) {
				$this->error("--duplicate and --backup are exclusive, ignoring --backup");
			}
			$this->verbose_log("Backing up files with matches");
		}
		$this->verbose_log(" Search: $search (" . lang::plural_word("character", strlen($search)) . ")");
		$this->verbose_log("Replace: $replace (" . lang::plural_word("character", strlen($replace)) . ")");
		$stats = array(
			'files' => 0,
			'lines' => 0
		);
		foreach ($files as $file) {
			$result = $this->_replace_file($file, $search, $replace);
			if ($result > 0) {
				$stats['files']++;
				$stats['lines'] += $result;
			}
		}
		$this->log(text::format_pairs($stats));
	}
	private function _list_files($dir, array $extensions) {
		$options = array();
		$options['file_include_pattern'] = '#\.' . implode("|", $extensions) . '$#';
		$options['file_exclude_pattern'] = '#.*/\.#';
		$options['file_default'] = false;
		$options['directory_default'] = false;
		$options['directory_walk_exclude_pattern'] = '#.*/\.#';
		$options['add_path'] = true;

		return dir::list_recursive($dir, $options);
	}
	private function _replace_file($file, $search, $replace) {
		$backup = $this->option_bool('backup');
		$duplicate = $this->option_bool('duplicate');
		$show = $this->option_bool('show');
		$dry_run = $this->option_bool('dry-run');
		$contents = file_get_contents($file);
		if (strpos($contents, $search) === false) {
			$this->debug_log("$file: No matches");
			return 0;
		}
		$rabbit = "\x01";
		$search_tr = array(
			$search => $rabbit
		);
		$replace_tr = array(
			$rabbit => $replace
		);

		$lines = explode("\n", strtr($contents, $search_tr));
		foreach ($lines as $lineno => $line) {
			if (strpos($line, $rabbit) === false) {
				unset($lines[$lineno]);
			}
		}
		if ($dry_run || $show) {
			echo "$file: " . lang::plural_word("match", count($lines)) . "\n";
			$carrots_tr = array(
				$rabbit => str_repeat("^", strlen($replace))
			);
			foreach ($lines as $lineno => $line) {
				$line = strtr($line, array(
					"\t" => "    "
				));
				echo text::ralign($lineno + 1, 4) . ": " . strtr($line, array(
					$rabbit => $search
				)) . "\n";
				echo text::ralign($lineno + 1, 4) . ": " . strtr($line, $replace_tr) . "\n";
				$carrot_line = preg_replace("#[^$rabbit]#", " ", $line);
				echo text::ralign("", 4) . "  " . strtr($carrot_line, $carrots_tr) . "\n";
			}
			return count($lines);
		}
		if ($duplicate) {
			$ext = file::extension($file);
			$dupfile = file::extension_change($file, ".cannon.$ext");
			$this->verbose_log("Writing $dupfile: " . lang::plural_word("change", count($lines)));
			file_put_contents($dupfile, strtr($contents, array(
				$search => $replace
			)));
		} else {
			if ($backup) {
				log::rotate($file, null, 3, ".old");
			}
			file_put_contents($file, strtr($contents, array(
				$search => $replace
			)));
		}
		return count($lines);
	}
}
