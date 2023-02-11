<?php
declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/command/check.php $
 * @package zesk
 * @subpackage bin
 * @category Debugging
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use SplFileInfo;

/**
 * Check PHP code, and repair comments.
 * @category Debugging
 */
class Command_Check extends Command_Iterator_File {
	protected array $shortcuts = ['check', 'ck'];

	/**
	 *
	 * @var array
	 */
	protected array $extensions = [
		'php', //		"phpt",
		'inc', 'tpl', 'module',
	];

	/**
	 *
	 * @var array
	 */
	protected array $prefixes = [
		'php' => [
			'<?' . "php\ndeclare(strict_types=1);\n/**\n",
			"#!{php_bin_path}\n<?php\ndeclare(strict_types=1);\n/**\n",
		],
		'inc' => '<?' . "php\n/**\n",
		'tpl' => '<?' . "php\n",
		//		"phpt" => "#!{php_bin_path}\n<?php\n"
	];

	/**
	 *
	 * @var array
	 */
	protected array $prefixes_gremlins = [
		'php' => [
			'<?' . "php\n", '<?' . "php \n", "#!{php_bin_path}\n<?php\n",
		], 'tpl' => '<?' . "php\n", 'inc' => [
			'<?' . "php\n", '<?' . "php \n",
		],
		//		"phpt" => "#!{php_bin_path}\n<?php\n"
	];

	/**
	 *
	 * @var array
	 */
	protected array $log = [];

	//	protected $debug = true;
	protected bool $show = false;

	protected int $changed = 0;

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
		$this->prefixes = map($this->prefixes, $this->options(['php-bin-path']));
		$this->prefixes_gremlins = map($this->prefixes_gremlins, $this->options(['php-bin-path']));
		if ($this->optionBool('show-package') || $this->optionBool('show-subpackage') || $this->optionBool('show-author') || $this->optionBool('show-copyright')) {
			$this->show = true;
			$this->verboseLog('Show is on.');
		}
		$this->verboseLog('Fix is ' . ($this->optionBool('fix') ? 'on' : 'off') . '.');
		$this->changed = 0;
	}

	private function lint_file($path, &$output = null): int {
		$result_var = 255;
		$options = ' -d error_reporting=\'E_ALL|E_STRICT\'';
		exec("php -l$options \"$path\" 2>&1", $output, $result_var);
		if ($result_var !== 0) {
			$this->verboseLog("lint result is $result_var");
		}
		return intval($result_var);
	}

	private function lint_php(string $php_code): int {
		$tmp = path(ZESK_ROOT, '.' . md5($php_code) . '-' . $this->application->process->id() . '.php');
		file_put_contents($tmp, $php_code);
		$result = self::lint_file($tmp);
		unlink($tmp);
		return $result;
	}

	/**
	 * @param string $contents
	 * @param string $term
	 * @param callable $fixFunction
	 * @param callable $addFunction
	 * @return bool
	 */
	private function updateComment(string &$contents, string $term, callable $fixFunction, callable $addFunction): bool {
		$translate = [];
		$comment_options = $this->application->configuration->path(DocComment::class)->toArray();
		$comments = DocComment::extract($contents, $comment_options);
		foreach ($comments as $comment) {
			/* @var $comment DocComment */
			$indent_text = '';
			$match = null;
			$source = $comment->content();
			if (preg_match('#$([ \t]*)/\*\*#', $source, $match)) {
				$indent_text = $match[1];
			}
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				$new_value = call_user_func($fixFunction, $items[$term], $term);
				if ($new_value !== $items[$term]) {
					$items[$term] = $new_value;
					$translate[$source] = Text::indent(DocComment::instance($items, $comment_options)->content(), $indent_text);
				}
			} elseif ($this->isAdd() && $addFunction) {
				$new_value = call_user_func($addFunction, $items, $term);
				if (is_array($new_value)) {
					$translate[$source] = DocComment::instance($new_value, $comment_options)->content();
					$addFunction = null; /* Just first one */
				}
			}
		}
		if (count($translate) === 0) {
			return false;
		}
		$contents = strtr($contents, $translate);
		return true;
	}

	/**
	 * @param string $contents
	 * @param string $term
	 * @return array
	 */
	private function showComments(string $contents, string $term): array {
		$comments = DocComment::extract($contents);
		$results = [];
		foreach ($comments as $comment) {
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				$results[] = "@$term " . $items[$term];
			}
		}
		return $results;
	}

	/**
	 * @param string $contents
	 * @param string $term
	 * @return DocComment
	 * @throws Exception_NotFound
	 */
	private function firstComment(string $contents, string $term): DocComment {
		$comments = DocComment::extract($contents);
		foreach ($comments as $comment) {
			$items = $comment->variables();
			if (array_key_exists($term, $items)) {
				return $items[$term];
			}
		}

		throw new Exception_NotFound($term);
	}

	private function fix_copyright(string $value): string {
		return preg_replace('/([^0-9])[12][09][0-9][0-9]([^0-9])/', '${1}' . date('Y') . '${2}', $value);
	}

	private function copyright_pattern(): string {
		return '&copy; {year} {company}{copyright_suffix}';
	}

	private function add_copyright(array $doccomment): array {
		$doccomment['copyright'] = map($this->copyright_pattern(), $this->options());
		return $doccomment;
	}

	private function fix_prefix(string &$contents): bool {
		$contents = ltrim($contents);
		$author = $this->application->process->user();
		$new_prefix = map("<?php\n/**\n * @author $author\n * @package {package}\n * @subpackage {subpackage}\n * @copyright " . $this->copyright_pattern() . "\n */\n", $this->options());
		foreach ([
			'#^(<\?php)#', '#^(<\?)[^=]#',
		] as $pattern) {
			if (preg_match($pattern, $contents, $match)) {
				$contents = implode($new_prefix, explode($match[1], $contents, 2));
				return true;
			}
		}
		$contents = $new_prefix . "?>\n" . $contents;
		return true;
	}

	private function fix_suffix(&$contents): bool {
		$contents = rtrim($contents);
		$contents = rtrim(StringTools::removeSuffix($contents, '?>')) . "\n";
		return true;
	}

	private function fix_author(string $value): string {
		return $_SERVER['USER'] ?? $value;
	}

	private function doccomment_add_author(array $items, string $term) {
		$items[$term] = $this->fix_author('');
		return $items;
	}

	private function fix_package(string $value) {
		assert(is_string($value));
		return $this->option('package');
	}

	private function fix_subpackage($value) {
		assert(is_string($value));
		return $this->option('subpackage');
	}

	private function isAdd(): bool {
		return !$this->optionBool('update-only');
	}

	private function recopyright(string &$contents): bool {
		return $this->updateComment($contents, 'copyright', $this->fix_copyright(...), $this->add_copyright(...));
	}

	private function reauthor(string &$contents): bool {
		return $this->updateComment($contents, 'author', $this->fix_author(...), $this->doccomment_add_author(...));
	}

	private function set_package(string &$contents): bool {
		return $this->updateComment($contents, 'package', $this->fix_doccomment_option(...), $this->doccomment_add_option(...));
	}

	private function doccomment_add_option(array $items, string $option) {
		$items[$option] = $this->option($option);
		return $items;
	}

	private function fix_doccomment_option(string $value, string $option) {
		assert(is_string($value));
		return $this->option($option);
	}

	private function set_subpackage(&$contents) {
		return $this->updateComment($contents, 'subpackage', $this->fix_doccomment_option(...), $this->doccomment_add_option(...));
	}

	public function process_file(SplFileInfo $file): bool {
		$path = $file->getPathname();
		if ($this->hasOption('ignore')) {
			$ignore = $this->option('ignore');
			if (str_contains($path, $ignore)) {
				return true;
			}
		}
		$this->verboseLog("Processing $path ...");
		$contents = file_get_contents($path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$errors = [];
		$changed = false;
		$prefix = ($this->optionBool('gremlins') ? $this->prefixes_gremlins : $this->prefixes)[$ext] ?? null;
		if ($prefix !== null) {
			$prefix = toArray($prefix);
			if (!StringTools::begins($contents, $prefix)) {
				$details = substr($contents, 0, 40);
				$this->verboseLog("Incorrect prefix: \"{details}\"\n should be one of: {prefix}", compact('details') + [
					'prefix' => ArrayTools::joinWrap($prefix, '"', "\"\n"),
				]);
				if ($this->optionBool('fix') && $this->fix_prefix($contents)) {
					$changed = true;
					$this->verboseLog('Fixed prefix');
				} else {
					$errors['prefix'] = $details;
				}
			}
		}
		$multi_tag = count(explode('<?', $contents)) > 2;
		if ($multi_tag) {
			$this->verboseLog('Multi-tag file');
		}
		if (StringTools::ends(trim($contents), '?>')) {
			$this->verboseLog('Need to trim PHP closing tag');
			$details = substr($contents, 0, 40);
			if ($this->optionBool('fix') && $this->fix_suffix($contents)) {
				$this->verboseLog('Fixed suffix');
				$changed = true;
			} else {
				$errors['suffix'] = $details;
			}
		}
		if ($this->optionBool('fix') && !StringTools::ends($contents, "\n")) {
			$this->verboseLog('Terminate with newline');
			$contents .= "\n";
			$changed = true;
		}
		$output = null;
		if ($this->optionBool('lint') && self::lint_file($path, $output) !== 0) {
			$errors['lint'] = $output;
		}
		if ($this->optionBool('copyright') && $this->recopyright($contents)) {
			$this->verboseLog('copyright changed');
			$changed = true;
		}
		if ($this->optionBool('author') && $this->reauthor($contents)) {
			$this->verboseLog('author changed');
			$changed = true;
		}
		if ($this->option('package') && $this->set_package($contents)) {
			$this->verboseLog('package changed');
			$changed = true;
		}
		if ($this->option('subpackage') && $this->set_subpackage($contents)) {
			$this->verboseLog('subpackage changed');
			$changed = true;
		}
		if ($this->show) {
			$results = [];
			foreach ([
				'show-package', 'show-subpackage', 'show-author', 'show-copyright',
			] as $option) {
				if ($this->optionBool($option)) {
					$results = array_merge($results, $this->showComments($contents, StringTools::removePrefix($option, 'show-', $prefix)));
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
			$this->verboseLog('File changed ...');
			if ($this->lint_php($contents) !== 0) {
				file_put_contents("$path.failed", $contents);
				$this->log("Lint failed on modified file, see $path.failed");
				$errors['modified-fail-lint'] = true;
				$this->log[$path] = $errors;
				return false; // Stop processing
			}
			if (!$this->optionBool('no-backup')) {
				if (file_exists($path . '.old')) {
					$this->log("$path.old exists, skipping");
					return true;
				}
				if (!rename($path, "$path.old")) {
					$this->log("Can not rename $path to $path.old, skipping");
					return true;
				}
			} elseif ($this->optionBool('safe')) {
				$ext = File::extension($path);
				$path = StringTools::removeSuffix($path, ".$ext") . ".new.$ext";
			}
			$this->verboseLog("Writing $path");
			file_put_contents($path, $contents);
			$this->changed++;
		}
		return true;
	}

	protected function finish(): int {
		$n_found = count($this->log);
		if ($n_found === 0) {
			$this->verboseLog('No issues found.');
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
		return 1;
	}
}
