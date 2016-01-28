<?php
abstract class Command_File_Convert extends Command_Base {

	protected $source_extension_pattern = null;
	protected $destination_extension = null;
	protected $configuration_file = "file-convert";

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

	protected function run() {
		$this->verbose_log("Configuring using config file: " . $this->configuration_file);
		$this->configure($this->configuration_file);
		$app = Application::instance();
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
				$files = dir::list_recursive($cwd, array(
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
				$new_file = file::extension_change($file, ".$extension");
				if (!$force && is_file($new_file) && filesize($new_file) > 0) {
					if ($noclobber) {
						log::debug("noclobber: Will not overwrite: $new_file");
						continue;
					}
					if (!$nomtime) {
						$new_mtime = filemtime($new_file);
						$mtime = filemtime($file);
						if ($new_mtime > $mtime) {
							log::debug("Modification time, skipping: $new_file");
							continue;
						}
					}
				}
				if ($dry_run) {
					log::notice("Would write $new_file");
					continue;
				}
				if (!$this->convert_file($file, $new_file)) {
					log::error("unable to convert from {file} to {new_file}", compact("file", "newfile"));
				}
			}
		} else {
			echo $this->convert_fp(STDIN);
		}
	}

	protected function convert_fp($fp) {
		$content = "";
		while (!feof($fp)) {
			$content .= fread($fp, 1024);
		}
		return $this->convert_raw($content);
	}

	abstract protected function convert_file($file, $new_file);

	abstract protected function convert_raw($content);

	final protected function default_convert_file($file, $new_file) {
		log::notice("Writing $new_file");
		return file_put_contents($new_file, $this->convert_raw(file_get_contents($file)));
	}

	final function default_convert_raw($content) {
		$src = file::temporary(".source");
		$dst = file::temporary("." . $this->destination_extension);
		file_put_contents($src, $content);
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
