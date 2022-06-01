<?php declare(strict_types=1);

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/command/check.php $
 * @package zesk
 * @subpackage bin
 * @category Debugging
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

use \SplFileInfo;

/**
 * Check PHP code, and repair comments.
 * @category Debugging
 */
class Command_Check extends Command_Iterator_File {
	/**
	 *
	 * @var array
	 */
	protected $extensions = [
		'php',
		//		"phpt",
		'inc',
		'tpl',
		'module',
	];

	/**
	 *
	 * @var array
	 */
	protected $prefixes = [
		'php' => [
			"<?php\n/**\n",
			"#!{php_bin_path}\n<?php\n/**\n",
		],
		'inc' => "<?php\n/**\n",
		'tpl' => "<?php\n",
		//		"phpt" => "#!{php_bin_path}\n<?php\n"
	];

	/**
	 *
	 * @var array
	 */
	protected $prefixes_gremlins = [
		'php' => [
			"<?php\n",
			"<?php \n",
			"#!{php_bin_path}\n<?php\n",
		],
		'tpl' => "<?php\n",
		'inc' => [
			"<?php\n",
			"<?php \n",
		],
		//		"phpt" => "#!{php_bin_path}\n<?php\n"
	];

	/**
	 *
	 * @var array
	 */
	protected $log = [];

	//	protected $debug = true;
	protected $show = false;

	protected $changed = 0;

	public function initialize(): void {
		parent::initialize();

		$this->option_types['prefix'] = 'string';
		$this->option_types['suffix'] = 'string';

		$this->option_types['lint'] = 'boolean';
		$this->option_types['php-bin-path'] = 'string';

		$this->option_types['copyright'] = 'boolean';
		$this->option_types['company'] = 'string';
		$this->option_types['copyright-suffix'] = 'string';

		$this->option_types['author'] = 'boolean';
		$this->option_types['package'] = 'string';
		$this->option_types['subpackage'] = 'string';

		$this->option_types['year'] = 'string';

		$this->option_types['show-package'] = 'boolean';
		$this->option_types['show-subpackage'] = 'boolean';
		$this->option_types['show-author'] = 'boolean';
		$this->option_types['show-copyright'] = 'boolean';

		$this->option_types['update-only'] = 'boolean';
		$this->option_types['gremlins'] = 'boolean';
		$this->option_types['fix'] = 'boolean';
		$this->option_types['safe'] = 'boolean';
		$this->option_types['no-backup'] = 'boolean';
		$this->option_types['ignore'] = 'string';

		$this->setOption('copyright-suffix', '');
		$this->setOption('company', '');

		$this->option_help['gremlins'] = 'Check file headers for incorrect headings, ensure all PHP files have no characters before first PHP tag.';
		$this->option_help['fix'] = 'Actually modify and fix files';

		$this->option_help['prefix'] = 'File output prefix';
		$this->option_help['suffix'] = 'File output suffix';

		$this->option_help['lint'] = 'Run PHP lint on each file as well';
		$this->option_help['php-bin-path'] = 'Path to PHP binary (uses \$PATH otherwise)';

		$this->option_help['update-only'] = 'Do not add in missing doccomments, just update existing ones.';

		$this->option_help['copyright-suffix'] = 'The suffix after the copyright (e.g. "Buy N Large, Inc.")';

		$this->option_help['copyright'] = 'Update the copyright string';
		$this->option_help['author'] = 'Update the author to be \$Author\$';
		$this->option_help['package'] = 'Set the doccomment package';
		$this->option_help['subpackage'] = 'Set the doccomment subpackage';
		$this->option_help['company'] = 'Copyright company';
		$this->option_help['year'] = 'Set the copyright year to be this year (uses current year otherwise)';

		$this->option_help['show-package'] = 'Output the package for each file';
		$this->option_help['show-subpackage'] = 'Output the subpackage for each file';
		$this->option_help['show-author'] = 'Output the author for each file';
		$this->option_help['show-copyright'] = 'Output the copyright for each file';

		$this->option_help['safe'] = 'Create a new file called name.new.ext';
		$this->option_help['no-backup'] = 'Copy original to name.ext.old';
		$this->option_help['ignore'] = 'Ignore file paths containing this string';

		$this->setOption('php-bin-path', '/usr/bin/env php');
		$this->setOption('year', date('Y'));
	}

	protected function start(): void {
		$this->prefixes = map($this->prefixes, $this->options_include('php-bin-path'));
		$this->prefixes_gremlins = map($this->prefixes_gremlins, $this->options_include('php-bin-path'));
		if ($this->optionBool('show-package') || $this->optionBool('show-subpackage') || $this->optionBool('show-author') || $this->optionBool('show-copyright')) {
			$this->show = true;
			$this->verbose_log('Show is on.');
		}
		$this->verbose_log('Fix is ' . ($this->optionBool('fix') ? 'on' : 'off') . '.');
		$this->changed = 0;
	}

	private function lint_file($path, &$output = null) {
		$result_var = 255;
		$options = ' -d error_reporting=\'E_ALL|E_STRICT\'';
		exec("php -l$options \"$path\" 2>&1", $output, $result_var);
		if ($result_var !== 0) {
			$this->verbose_log("lint result is $result_var");
		}
		return intval($result_var);
	}

	private function lint_php($php_code) {
		$tmp = path(ZESK_ROOT, '.' . md5($php_code) . '-' . $this->application->process->id() . '.php');
		file_put_contents($tmp, $php_code);
		$result = self::lint_file($tmp);
		unlink($tmp);
		return $result;
	}

	private function recomment(&$contents, $term, $function, $add_function = null) {
		$translate = [];
		$comment_options = $this->application->configuration->path(DocComment::class)->to_array();
		$comments = DocComment::extract($contents, $comment_options);
		foreach ($comments as $comment) {
			/* @var $comment DocComment */
			$indent_text = '';
			$match = null;
			if (preg_match('#$([ \t]*)/\*\*#', $comment, $match)) {
				$indent_text = $match[1];
			}
			$source = $comment->content();
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				$new_value = call_user_func($function, $items[$term]);
				if ($new_value !== $items[$term]) {
					$items[$term] = $new_value;
					$translate[$source] = Text::indent(DocComment::instance($items, $comment_options)->content(), $indent_text);
				}
			} elseif ($add_function) {
				$new_value = call_user_func($add_function, $items, $term);
				if (is_array($new_value)) {
					$translate[$source] = DocComment::instance($new_value, $comment_options)->content();
					$add_function = null; // Just first one
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
		$translate = [];
		$comments = DocComment::extract($contents);
		$results = [];
		foreach ($comments as $comment) {
			/* @var $comment DocComment */
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				$results[] = "@$term " . $items[$term];
			}
		}
		return $results;
	}

	private function first_comment($contents, $term) {
		$translate = [];
		$comments = DocComment::extract($contents);
		foreach ($comments as $comment) {
			/* @var $comment DocComment */
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				return $items[$term];
			}
		}
		return null;
	}

	private function fix_copyright($value) {
		return preg_replace('/([^0-9])[12][09][0-9][0-9]([^0-9])/', '${1}' . date('Y') . '${2}', $value);
	}

	private function copyright_pattern() {
		return '&copy; {year} {company}{copyright_suffix}';
	}

	private function add_copyright(array $doccomment) {
		$doccomment['copyright'] = map($this->copyright_pattern(), $this->options());
		return $doccomment;
	}

	private function fix_prefix(&$contents) {
		$contents = ltrim($contents);
		$author = $this->application->process->user();
		$new_prefix = map("<?php\n/**\n * @author $author\n * @package {package}\n * @subpackage {subpackage}\n * @copyright " . $this->copyright_pattern() . "\n */\n", $this->options());
		foreach ([
			'#^(<\?php)#',
			'#^(<\?)[^=]#',
		] as $pattern) {
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
		$contents = rtrim(StringTools::unsuffix($contents, '?>')) . "\n";
		return true;
	}

	private function fix_author($value) {
		return '$' . 'Author' . '$';
	}

	private function doccomment_add_author(array $items, $term) {
		$items[$term] = $this->fix_author('');
		return $items;
	}

	private function fix_package($value) {
		return $this->option('package');
	}

	private function fix_subpackage($value) {
		return $this->option('subpackage');
	}

	private function is_add() {
		return !$this->optionBool('update-only');
	}

	private function recopyright(&$contents) {
		return $this->recomment($contents, 'copyright', [
			$this,
			'fix_copyright',
		], $this->is_add() ? [
			$this,
			'add_copyright',
		] : null);
	}

	private function reauthor(&$contents) {
		return $this->recomment($contents, 'author', [
			$this,
			'fix_author',
		], $this->is_add() ? [
			$this,
			'doccomment_add_author',
		] : null);
	}

	private function set_package(&$contents) {
		return $this->recomment($contents, 'package', [
			$this,
			'fix_package',
		], $this->is_add() ? [
			$this,
			'doccomment_add_option',
		] : null);
	}

	private function doccomment_add_option(array $items, $option) {
		$items[$option] = $this->option($option);
		return $items;
	}

	private function set_subpackage(&$contents) {
		return $this->recomment($contents, 'subpackage', [
			$this,
			'fix_subpackage',
		], $this->is_add() ? [
			$this,
			'doccomment_add_option',
		] : null);
	}

	public function process_file(SplFileInfo $file): void {
		$path = $file->getPathname();
		if ($this->hasOption('ignore')) {
			$ignore = $this->option('ignore');
			if (str_contains($path, $ignore)) {
				return;
			}
		}
		$this->verbose_log("Processing $path ...");
		$contents = file_get_contents($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$errors = [];
		$changed = false;
		$prefix = avalue($this->optionBool('gremlins') ? $this->prefixes_gremlins : $this->prefixes, $ext);
		if ($prefix !== null) {
			$prefix = to_array($prefix);
			if (!StringTools::begins($contents, $prefix)) {
				$details = substr($contents, 0, 40);
				$this->verbose_log("Incorrect prefix: \"{details}\"\n should be one of: {prefix}", compact('details') + [
					'prefix' => ArrayTools::joinWrap($prefix, '"', "\"\n"),
				]);
				if ($this->optionBool('fix') && $this->fix_prefix($contents)) {
					$changed = true;
					$this->verbose_log('Fixed prefix');
				} else {
					$errors['prefix'] = $details;
				}
			}
		}
		$multi_tag = count(explode('<?', $contents)) > 2;
		if ($multi_tag) {
			$this->verbose_log('Multi-tag file');
		}
		if (StringTools::ends(trim($contents), '?>')) {
			$this->verbose_log('Need to trim PHP closing tag');
			$details = substr($contents, 0, 40);
			if ($this->optionBool('fix') && $this->fix_suffix($contents)) {
				$this->verbose_log('Fixed suffix');
				$changed = true;
			} else {
				$errors['suffix'] = $details;
			}
		}
		if ($this->optionBool('fix') && !StringTools::ends($contents, "\n")) {
			$this->verbose_log('Terminate with newline');
			$contents .= "\n";
			$changed = true;
		}
		$output = null;
		if ($this->optionBool('lint') && self::lint_file($path, $output) !== 0) {
			$errors['lint'] = $output;
		}
		if ($this->optionBool('copyright') && $this->recopyright($contents)) {
			$this->verbose_log('copyright changed');
			$changed = true;
		}
		if ($this->optionBool('author') && $this->reauthor($contents)) {
			$this->verbose_log('author changed');
			$changed = true;
		}
		if ($this->option('package') && $this->set_package($contents)) {
			$this->verbose_log('package changed');
			$changed = true;
		}
		if ($this->option('subpackage') && $this->set_subpackage($contents)) {
			$this->verbose_log('subpackage changed');
			$changed = true;
		}
		if ($this->show) {
			$results = [];
			foreach ([
				'show-package',
				'show-subpackage',
				'show-author',
				'show-copyright',
			] as $option) {
				if ($this->optionBool($option)) {
					$results = array_merge($results, $this->show_comments($contents, StringTools::unprefix($option, 'show-', $prefix)));
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
			$this->verbose_log('File changed ...');
			if ($this->lint_php($contents) !== 0) {
				file_put_contents("$path.failed", $contents);
				$this->log("Lint failed on modified file, see $path.failed");
				$errors['modified-fail-lint'] = true;
				$this->log[$path] = $errors;
				return;
			}
			if (!$this->optionBool('no-backup')) {
				if (file_exists($path . '.old')) {
					$this->log("$path.old exists, skipping");
					return;
				}
				if (!rename($path, "$path.old")) {
					$this->log("Can not rename $path to $path.old, skipping");
					return;
				}
			} elseif ($this->optionBool('safe')) {
				$ext = File::extension($path);
				$path = StringTools::unsuffix($path, ".$ext") . ".new.$ext";
			}
			$this->verbose_log("Writing $path");
			file_put_contents($path, $contents);
			$this->changed++;
		}
	}

	protected function finish() {
		$n_found = count($this->log);
		if ($n_found === 0) {
			$this->verbose_log('No issues found.');
			return 0;
		}
		$prefix = trim($this->option('prefix', ''));
		$suffix = trim($this->option('suffix', ''));
		if ($prefix) {
			$prefix = "$prefix ";
		}
		if ($suffix) {
			$suffix = " $suffix";
		}
		$locale = $this->application->locale;
		$verbose = $this->optionBool('verbose');
		if ($verbose) {
			echo '# ' . $locale->plural_word('error', $n_found) . " found\n";
		}
		$results = [];
		if ($this->optionBool('verbose')) {
			foreach ($this->log as $f => $errors) {
				$results[] = $prefix . $f . $suffix . ' # ' . implode(', ', array_keys($errors));
			}
		} else {
			foreach ($this->log as $f => $errors) {
				$results[] = $prefix . $f . $suffix;
			}
		}
		if ($verbose) {
			echo $locale->plural_word('file', $this->changed) . " changed\n";
		}
		echo implode("\n", $results) . "\n";
	}
}
