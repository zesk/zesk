<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use Throwable;
use zesk\Directory;
use zesk\Exception;
use zesk\Exception\FilePermission;
use zesk\Exception\SyntaxException;
use zesk\File;

/**
 * Abstract class for iterating on a series of files and converting them from one syntax to another
 * @author kent
 * @api
 *
 */
abstract class FileConverter extends SimpleCommand {
	/**
	 * Scan for files matching this extension pattern (no delimiters), use vertical bar only
	 *
	 * e.g. Use "inc|php|tpl" to match files ending with those extensions
	 *
	 * @var string
	 */
	protected string $source_extension_pattern = '';

	/**
	 *
	 * @var string
	 */
	protected string $destination_extension = '';

	/**
	 * Overwrite files (when true, implies destination_extension is null)
	 *
	 * @var boolean
	 */
	protected bool $overwrite = false;

	/**
	 * Override in subclasses to modify the configuration file loaded by this command.
	 *
	 * @var string
	 */
	protected string $configuration_file = 'file-convert';

	/**
	 *
	 * {@inheritDoc}
	 * @see SimpleCommand::initialize()
	 */
	public function initialize(): void {
		$this->option_types += [
			'nomtime' => 'boolean', 'noclobber' => 'boolean', 'extension' => 'string', 'dry-run' => 'boolean',
			'force' => 'boolean', 'target-path' => 'string', 'mkdir-target' => 'boolean', '*' => 'files',
		];
		$this->setOptions([
			'extension' => $this->destination_extension, 'target-path' => './',
		], false);
		$this->option_help += [
			'nomtime' => 'Ignore destination file modification time when determining whether to generate',
			'noclobber' => 'Do not overwrite existing files',
			'extension' => 'Use this extenstion for the generated files instead of the default',
			'dry-run' => 'Don\'t make any changes, just show what would happen.', 'force' => 'Always write files',
			'target-path' => 'When converting files, create targets here (e.g. `../compiled-js/`)',
			'mkdir-target' => 'Create target directory if does not exist, then convert`)',
			'*' => 'A list of files to process',
		];
		parent::initialize();
	}

	/**
	 * @throws SyntaxException
	 */
	private function target_filename(string $file): string {
		$extension = trim($this->option('extension', $this->destination_extension), '.');
		$target_prefix = $this->option('target-path');
		$new_file = $this->overwrite ? $file : File::setExtension($file, ".$extension");
		$new_file = path(dirname($new_file), $target_prefix, basename($new_file));
		return Directory::removeDots($new_file);
	}

	/**
	 *
	 */
	protected function run(): int {
		$this->verboseLog('Configuring using config file: ' . $this->configuration_file);
		$this->configure($this->configuration_file);
		$app = $this->application;
		$app->setConsole(true);
		$request = $app->requestFactory();
		$app->template->set([
			'request' => $request, 'response' => $app->responseFactory($request), 'stylesheets_inline' => true,
		]);
		if ($this->overwrite && $this->optionBool('no-clobber')) {
			$this->error('The --no-clobber option is not compatible with this command, it can only overwrite existing files.');
		}
		$dry_run = $this->optionBool('dry-run');
		if ($dry_run) {
			$this->setOption('verbose', true);
		}
		stream_set_blocking(STDIN, 0);
		$content = fread(STDIN, 1024);
		fseek(STDIN, 0);
		if ($content === false || $content === '') {
			$args = $this->argumentsRemaining();
			if (count($args)) {
				$files = $args;
			} else {
				$cwd = getcwd();
				$this->verboseLog('Listing {cwd}', compact('cwd'));
				$files = Directory::listRecursive($cwd, [
					Directory::LIST_RULE_FILE => [
						'/\.(' . $this->source_extension_pattern . ')$/' => true, false,
					], Directory::LIST_RULE_DIRECTORY_WALK => [
						'#/\.#' => false, true,
					], Directory::LIST_RULE_DIRECTORY => false, Directory::LIST_ADD_PATH => true,
				]);
			}
			$overwrite = $this->overwrite;
			$force = $this->optionBool('force') || $overwrite;
			$noclobber = $this->optionBool('noclobber');
			$nomtime = $this->optionBool('nomtime');
			$target_prefix = $this->option('target-path');
			$mkdir_target = $this->optionBool('mkdir-target');
			foreach ($files as $file) {
				if (!file_exists($file)) {
					continue;
				}
				$new_file = $this->target_filename($file);
				$new_path = dirname($new_file);
				if (!$force && is_file($new_file) && filesize($new_file) > 0) {
					if ($noclobber) {
						$this->application->debug("noclobber: Will not overwrite: $new_file");

						continue;
					}
					if (!$nomtime) {
						$new_mtime = filemtime($new_file);
						$mtime = filemtime($file);
						if ($new_mtime > $mtime) {
							$this->application->debug("Modification time, skipping: $new_file");

							continue;
						}
					}
				}
				if ($dry_run) {
					$this->application->notice("Would write $new_file");

					continue;
				}
				if (!is_dir($new_path) && $mkdir_target) {
					Directory::depend($new_path);
				} else {
					$this->application->debug('Skipping convert {file} because {new_path} does not exist', compact('file', 'new_path'));
				}

				try {
					$this->convert_file($file, $new_file);
				} catch (Throwable $t) {
					$this->application->error('unable to convert from {file} to {new_file}: {throwableClass} {throwableMessage}', [
						'file' => $file, 'newFile' => $new_file,
					] + Exception::exceptionVariables($t));
				}
			}
		} else {
			echo $this->convert_fp(STDIN);
		}
		return 0;
	}

	/**
	 * Default implementation of "convert_fp" which loads the file until feof($fp) and does the conversion in memory.
	 *
	 * @param resource $fp File opened for reading with file pointer cued to the file start position.
	 * @api
	 */
	protected function convert_fp(mixed $fp): string {
		$content = '';
		while (!feof($fp)) {
			$content .= fread($fp, 1024);
		}
		return $this->convert_raw($content);
	}

	/**
	 * Convert $file into $new_file
	 *
	 * @param string $file
	 * @param string $new_file
	 * @api
	 */
	abstract protected function convert_file(string $file, string $new_file): void;

	/**
	 * Convert in memory and return converted entity
	 *
	 * @param string $content
	 * @api
	 */
	abstract protected function convert_raw(string $content): string;

	/**
	 * @param string $file
	 * @param string $new_file
	 * @return void
	 * @throws FilePermission
	 */
	final protected function default_convert_file(string $file, string $new_file): void {
		$this->application->notice('Writing {new_file}', compact('new_file'));
		File::put($new_file, $this->convert_raw(file_get_contents($file)));
	}

	/**
	 *
	 * @param unknown $content
	 * @return string
	 * @throws SyntaxException
	 */
	final protected function default_convert_raw($content) {
		$src = File::temporary($this->application->paths->temporary(), '.source');
		$dst = File::temporary($this->application->paths->temporary(), '.' . $this->destination_extension);
		File::put($src, $content);
		$result = $this->convert_file($src, $dst);
		if ($result) {
			$contents = file_get_contents($dst);
			unlink($dst);
			@unlink($src);
			return $contents;
		}

		throw new SyntaxException('Unable to convert file - ambigious');
	}
}
