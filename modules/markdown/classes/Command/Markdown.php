<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Command_Markdown extends Command_File_Convert {
	protected $source_extension_pattern = "markdown|md|mdown";

	protected $destination_extension = "html";

	protected $configuration_file = "markdown";

	public function initialize(): void {
		$this->option_types += [
			'cd' => 'dir',
		];
		$this->option_help += [
			'cd' => 'Change to directory before running',
		];
		parent::initialize();
	}

	protected function run() {
		if ($this->has_option('cd')) {
			chdir($this->option('cd'));
		}
		return parent::run();
	}

	protected function convert_raw($content) {
		$result = $this->application->theme('markdown;page', Markdown::filter($content));
		return $result;
	}

	protected function convert_file($file, $new_file) {
		return $this->default_convert_file($file, $new_file);
	}
}
