<?php
/**
 * 
 */
namespace zesk;

use \SplFileInfo;

/**
 * Check PHP code, and repair comments.
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/command/check.php $
 * @package zesk
 * @subpackage bin
 * @category Debugging
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
class Command_Check extends Command_Iterator_File {
	protected $extensions = array(
		"php",
		"phpt",
		"inc",
		"tpl",
		"module"
	);
	protected $prefixes = array(
		"php" => array(
			"<?php\n/**\n",
			"#!{php_bin_path}\n<?php\n/**\n"
		),
		"inc" => "<?php\n/**\n",
		"tpl" => "<?php\n",
		"phpt" => "#!{php_bin_path}\n<?php\n"
	);
	protected $prefixes_gremlins = array(
		"php" => array(
			"<?php\n",
			"#!{php_bin_path}\n<?php\n"
		),
		"tpl" => "<?php\n",
		"inc" => "<?php\n",
		"phpt" => "#!{php_bin_path}\n<?php\n"
	);
	protected $log = array();
	protected $editor = null;
	
	//	protected $debug = true;
	protected $show = false;
	protected $changed = 0;
	function initialize() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		
		parent::initialize();
		
		$this->option_types['gremlins'] = 'boolean';
		$this->option_types['fix'] = 'boolean';
		$this->option_types['editor'] = 'string';
		$this->option_types['php-bin-path'] = 'string';
		$this->option_types['lint'] = 'boolean';
		$this->option_types['recopyright'] = 'boolean';
		$this->option_types['reauthor'] = 'boolean';
		$this->option_types['package'] = 'string';
		$this->option_types['company'] = 'string';
		$this->option_types['year'] = 'string';
		$this->option_types['subpackage'] = 'string';
		$this->option_types['repackage'] = 'string';
		$this->option_types['resubpackage'] = 'string';
		$this->option_types['show-package'] = 'boolean';
		$this->option_types['show-subpackage'] = 'boolean';
		$this->option_types['show-author'] = 'boolean';
		$this->option_types['show-copyright'] = 'boolean';
		$this->option_types['safe'] = 'boolean';
		$this->option_types['no-backup'] = 'boolean';
		$this->option_types['ignore'] = 'string';
		
		$this->set_option('php-bin-path', '/usr/bin/env php');
		$this->set_option('year', date('Y'));
		
		$this->editor = $this->option('editor', $this->default_editor());
	}
	
	/**
	 * Use TextMate on Mac OS X (default only)
	 * 
	 * @return string
	 */
	private function default_editor() {
		$distro = System::distro("distro");
		if ($distro === "Darwin") {
			return "mate";
		} else {
			return "vim";
		}
	}
	protected function start() {
		$this->prefixes = map($this->prefixes, $this->options_include("php-bin-path"));
		$this->prefixes_gremlins = map($this->prefixes_gremlins, $this->options_include("php-bin-path"));
		if ($this->option_bool('show-package') || $this->option_bool('show-subpackage') || $this->option_bool('show-author') || $this->option_bool('show-copyright')) {
			$this->show = true;
			$this->verbose_log("Show is on.");
		}
		$this->verbose_log("Fix is " . ($this->option_bool('fix') ? 'on' : 'off') . ".");
		$this->changed = 0;
	}
	private function lint_file($path, &$output = null) {
		$result_var = 255;
		$options = " -d error_reporting='E_ALL|E_STRICT'";
		exec("php -l$options \"$path\" 2>&1", $output, $result_var);
		if ($result_var !== 0) {
			$this->verbose_log("lint result is $result_var");
		}
		return intval($result_var);
	}
	private function lint_php($php_code) {
		$tmp = path(ZESK_ROOT, "." . md5($php_code) . "-" . zesk()->process->id() . ".php");
		file_put_contents($tmp, $php_code);
		$result = self::lint_file($tmp);
		unlink($tmp);
		return $result;
	}
	private function recomment(&$contents, $term, $function) {
		$translate = array();
		$comments = DocComment::extract($contents);
		foreach ($comments as $comment) {
			$indent_text = "";
			$match = null;
			if (preg_match('#$([ \t]*)/\*\*#', $comment, $match)) {
				$indent_text = $match[1];
			}
			$items = DocComment::parse($comment);
			if (array_key_exists($term, $items)) {
				$new_value = call_user_func($function, $items[$term]);
				if ($new_value !== $items[$term]) {
					$items[$term] = $new_value;
					$translate[$comment] = Text::indent(DocComment::unparse($items), $indent_text);
				}
			}
		}
		if (count($translate) === 0) {
			return false;
		}
		$contents = strtr($contents, $translate);
		return true;
	}
	private function show_comments($contents, $term) {
		$translate = array();
		$comments = DocComment::extract($contents);
		$results = array();
		foreach ($comments as $comment) {
			$items = DocComment::parse($comment);
			if (array_key_exists($term, $items)) {
				$results[] = "@$term " . $items[$term];
			}
		}
		return $results;
	}
	private function first_comment($contents, $term) {
		$translate = array();
		$comments = DocComment::extract($contents);
		foreach ($comments as $comment) {
			$items = DocComment::parse($comment);
			if (array_key_exists($term, $items)) {
				return $items[$term];
			}
		}
		return null;
	}
	private function fix_copyright($value) {
		return preg_replace("/([^0-9])[12][09][0-9][0-9]([^0-9])/", '${1}' . date('Y') . '${2}', $value);
	}
	private function fix_prefix(&$contents) {
		$contents = ltrim($contents);
		$new_prefix = map("<?php\n/**\n * @version \$URL\$\n * @author \$Author\$\n * @package {package}\n * @subpackage {subpackage}\n * @copyright Copyright (C) {year}, {company}. All rights reserved.\n */\n", $this->option());
		foreach (array(
			'#^(<\?php)#',
			'#^(<\?)[^=]#'
		) as $pattern) {
			if (preg_match($pattern, $contents, $match)) {
				$contents = implode($new_prefix, explode($match[1], $contents, 2));
				return true;
			}
		}
		$contents = $new_prefix . "?>\n" . $contents;
		return true;
	}
	private function fix_suffix(&$contents) {
		$contents = rtrim($contents);
		$contents = rtrim(str::unsuffix($contents, "?>")) . "\n";
		return true;
	}
	private function fix_author($value) {
		return '$' . 'Author' . '$';
	}
	private function fix_package($value) {
		return $this->option('repackage');
	}
	private function fix_subpackage($value) {
		return $this->option('resubpackage');
	}
	private function recopyright(&$contents) {
		return $this->recomment($contents, 'copyright', array(
			$this,
			'fix_copyright'
		));
	}
	private function reauthor(&$contents) {
		return $this->recomment($contents, 'author', array(
			$this,
			'fix_author'
		));
	}
	private function repackage(&$contents) {
		return $this->recomment($contents, 'package', array(
			$this,
			'fix_package'
		));
	}
	private function resubpackage(&$contents) {
		return $this->recomment($contents, 'subpackage', array(
			$this,
			'fix_subpackage'
		));
	}
	function process_file(SplFileInfo $file) {
		$path = $file->getPathname();
		if ($this->has_option('ignore')) {
			$ignore = $this->option('ignore');
			if (strpos($path, $ignore) !== false) {
				return;
			}
		}
		$this->verbose_log("Processing $path ...");
		$contents = file_get_contents($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$errors = array();
		$changed = false;
		$prefix = avalue($this->option_bool("gremlins") ? $this->prefixes_gremlins : $this->prefixes, $ext);
		if ($prefix !== null) {
			$prefix = to_array($prefix);
			if (!str::begins($contents, $prefix)) {
				$details = substr($contents, 0, 40);
				$this->verbose_log("Incorrect prefix: \"{details}\"\n should be one of: {prefix}", compact("details") + array(
					"prefix" => arr::join_wrap($prefix, "\"", "\"\n")
				));
				if ($this->option_bool('fix') && $this->fix_prefix($contents)) {
					$changed = true;
					$this->verbose_log("Fixed prefix");
				} else {
					$errors['prefix'] = $details;
				}
			}
		}
		$multi_tag = count(explode("<?", $contents)) > 2;
		if ($multi_tag) {
			$this->verbose_log("Multi-tag file");
		}
		if (str::ends(trim($contents), "?>")) {
			$this->verbose_log("Need to trim PHP closing tag");
			$details = substr($contents, 0, 40);
			if ($this->option_bool('fix') && $this->fix_suffix($contents)) {
				$this->verbose_log("Fixed suffix");
				$changed = true;
			} else {
				$errors['suffix'] = $details;
			}
		}
		if ($this->option_bool('fix') && !str::ends($contents, "\n")) {
			$this->verbose_log("Terminate with newline");
			$contents .= "\n";
			$changed = true;
		}
		$output = null;
		if ($this->option_bool('lint') && self::lint_file($path, $output) !== 0) {
			$errors['lint'] = $output;
		}
		if ($this->option_bool('recopyright') && $this->recopyright($contents)) {
			$this->verbose_log("copyright changed");
			$changed = true;
		}
		if ($this->option_bool('reauthor') && $this->reauthor($contents)) {
			$this->verbose_log("author changed");
			$changed = true;
		}
		if ($this->option('repackage') && $this->repackage($contents)) {
			$this->verbose_log("repackage changed");
			$changed = true;
		}
		if ($this->option('resubpackage') && $this->resubpackage($contents)) {
			$this->verbose_log("resubpackage changed");
			$changed = true;
		}
		if ($this->show) {
			$results = array();
			foreach (array(
				'show-package',
				'show-subpackage',
				'show-author',
				'show-copyright'
			) as $option) {
				if ($this->option_bool($option)) {
					$results = array_merge($results, $this->show_comments($contents, str::unprefix($option, 'show-', $prefix)));
				}
			}
			if (count($results) > 0) {
				echo "# $path\n" . implode("\n", $results) . "\n";
			}
		}
		if (count($errors) > 0) {
			$this->log[$path] = $errors;
		}
		if ($changed) {
			$this->verbose_log("File changed ...");
			if ($this->lint_php($contents) !== 0) {
				file_put_contents("$path.failed", $contents);
				$this->log("Lint failed on modified file, see $path.failed");
				$errors['modified-fail-lint'] = true;
				$this->log[$path] = $errors;
				return;
			}
			if (!$this->option_bool('no-backup')) {
				if (file_exists($path . ".old")) {
					$this->log("$path.old exists, skipping");
					return;
				}
				if (!rename($path, "$path.old")) {
					$this->log("Can not rename $path to $path.old, skipping");
					return;
				}
			} else if ($this->option_bool('safe')) {
				$ext = file::extension($path);
				$path = str::unsuffix($path, ".$ext") . ".new.$ext";
			}
			$this->verbose_log("Writing $path");
			file_put_contents($path, $contents);
			$this->changed++;
		}
	}
	protected function finish() {
		$n_found = count($this->log);
		if ($n_found === 0) {
			$this->verbose_log("No issues found.");
			return 0;
		}
		$editor = $this->editor . " ";
		echo "# " . Locale::plural_word("error", $n_found) . " found\n";
		$results = array();
		foreach ($this->log as $f => $errors) {
			$results[] = $f . " # " . implode(", ", array_keys($errors));
		}
		echo Locale::plural_word("file", $this->changed) . " changed\n";
		echo $editor . implode("\n$editor", $results) . "\n";
	}
}

