<?php
/**
 * 
 */
namespace zesk;

/**
 * Abstract class for iterating on a series of files and converting them from one syntax to another
 * @author kent
 * @api
 *
 */
abstract class Command_File_Convert extends Command_Base {
	
	/**
	 * Scan for files matching this extension pattern (no delimeters), use vertical bar only 
	 * 
	 * e.g. Use "inc|php|tpl" to match files ending with those extensions
	 *  
	 * @var string
	 */
	protected $source_extension_pattern = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $destination_extension = null;
	
	/**
	 * Override in subclasses to modify the configuration file loaded by this command.
	 * 
	 * @var string
	 */
	protected $configuration_file = "file-convert";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command_Base::initialize()
	 */
	function initialize() {
		$this->option_types += array(
			'nomtime' => 'boolean',
			'noclobber' => 'boolean',
			'extension' => 'string',
			'dry-run' => 'boolean',
			'force' => 'boolean',
			'*' => 'files'
		);
		$this->option_defaults += array(
			'extension' => $this->destination_extension
		);
		$this->option_help += array(
			'nomtime' => 'Ignore destination file modification time when determining whether to generate',
			'noclobber' => 'Do not overwrite existing files',
			'extension' => 'Use this extenstion for the generated markdown files instead of .html (the default)',
			'dry-run' => 'Don\'t make any changes, just show what would happen.',
			'force' => 'Always write files',
			'*' => 'A list of files to process'
		);
		parent::initialize();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	protected function run() {
		$this->verbose_log("Configuring using config file: " . $this->configuration_file);
		$this->configure($this->configuration_file);
		$app = $this->application;
		$app->template->set(array(
			"request" => Request::instance(),
			'response' => Response::instance(),
			'stylesheets_inline' => true
		));
		$dry_run = $this->option_bool('dry-run');
		if ($dry_run) {
			$this->set_option('verbose', true);
		}
		stream_set_blocking(STDIN, 0);
		$content = fread(STDIN, 1024);
		if ($content === false || $content === "") {
			$args = $this->arguments_remaining(true);
			if (count($args)) {
				$files = $args;
			} else {
				$cwd = getcwd();
				$this->verbose_log("Listing {cwd}", compact("cwd"));
				$files = Directory::list_recursive($cwd, array(
					'file_include_pattern' => '/\.(' . $this->source_extension_pattern . ')$/',
					'file_default' => false,
					'directory_default' => false,
					'directory_walk_exclude_pattern' => '#/\.#',
					'add_path' => true
				));
			}
			$force = $this->option_bool("force");
			$noclobber = $this->option_bool("noclobber");
			$nomtime = $this->option_bool("nomtime");
			$extension = trim($this->option("extension", $this->destination_extension), ".");
			foreach ($files as $file) {
				if (!file_exists($file)) {
					continue;
				}
				$new_file = File::extension_change($file, ".$extension");
				if (!$force && is_file($new_file) && filesize($new_file) > 0) {
					if ($noclobber) {
						$this->application->logger->debug("noclobber: Will not overwrite: $new_file");
						continue;
					}
					if (!$nomtime) {
						$new_mtime = filemtime($new_file);
						$mtime = filemtime($file);
						if ($new_mtime > $mtime) {
							$this->application->logger->debug("Modification time, skipping: $new_file");
							continue;
						}
					}
				}
				if ($dry_run) {
					$this->application->logger->notice("Would write $new_file");
					continue;
				}
				if (!$this->convert_file($file, $new_file)) {
					$this->application->logger->error("unable to convert from {file} to {new_file}", compact("file", "newfile"));
				}
			}
		} else {
			echo $this->convert_fp(STDIN);
		}
	}
	
	/**
	 * Default implementation of "convert_fp" which loads the file until feof($fp) and does the conversion in memory.
	 * 
	 * @api
	 * @param resource $fp File opened for reading with file pointer cued to the file start position.
	 */
	protected function convert_fp($fp) {
		$content = "";
		while (!feof($fp)) {
			$content .= fread($fp, 1024);
		}
		return $this->convert_raw($content);
	}
	
	/**
	 * Convert $file into $new_file
	 * 
	 * @api
	 * @param string $file
	 * @param string $new_file
	 */
	abstract protected function convert_file($file, $new_file);
	
	/**
	 * Convert in memory and return converted entity
	 * 
	 * @api
	 * @param string $content
	 */
	abstract protected function convert_raw($content);
	
	/**
	 * 
	 * @param unknown $file
	 * @param unknown $new_file
	 * @return number
	 */
	final protected function default_convert_file($file, $new_file) {
		zesk()->logger->notice("Writing {new_file}", compact("new_file"));
		return File::put($new_file, $this->convert_raw(file_get_contents($file)));
	}
	
	/**
	 * 
	 * @param unknown $content
	 * @throws Exception_Syntax
	 * @return string
	 */
	final protected function default_convert_raw($content) {
		$src = File::temporary(".source");
		$dst = File::temporary("." . $this->destination_extension);
		File::put($src, $content);
		$result = $this->convert_file($src, $dst);
		if ($result) {
			$contents = file_get_contents($dst);
			unlink($dst);
			@unlink($src);
			return $contents;
		}
		throw new Exception_Syntax("Unable to convert file - ambigious");
	}
}
