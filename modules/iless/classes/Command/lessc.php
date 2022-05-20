<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage iless
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\ILess;

use zesk\Command_File_Convert;

/**
 * Less compiler
 *
 * @author kent
 *
 */
class Command_LessC extends Command_File_Convert {
	/**
	 * What are we looking for?
	 *
	 * @var string
	 */
	protected $source_extension_pattern = 'less';

	/**
	 * What're we converting it into?
	 *
	 * @var string
	 */
	protected $destination_extension = 'css';

	/**
	 * How should this command be configured when running on its own
	 *
	 * @var string
	 */
	protected $configuration_file = 'lessc';

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_File_Convert::initialize()
	 */
	public function initialize(): void {
		$this->option_types += [
			'cd' => 'dir',
		];
		$this->option_help += [
			'cd' => 'Change to directory before running',
		];
		$this->option_defaults += [
			'extension' => $this->destination_extension,
			'target-path' => '../css/',
		];
		parent::initialize();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_File_Convert::run()
	 */
	protected function run() {
		if ($this->hasOption('cd')) {
			chdir($this->option('cd'));
		}
		return parent::run();
	}

	/**
	 *
	 * @return Module
	 */
	protected function iless_module() {
		return $this->application->iless_module();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_File_Convert::convert_raw()
	 */
	protected function convert_raw($content) {
		$compiler = $this->iless_module()->compiler();
		return $compiler->compile($content);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_File_Convert::convert_file()
	 */
	protected function convert_file($file, $new_file) {
		$compiler = $this->iless_module()->compiler();
		$compiler->compile_file($file, $new_file);
		return true;
	}
}
