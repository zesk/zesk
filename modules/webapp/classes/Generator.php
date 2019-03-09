<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Hookable;
use zesk\File;

abstract class Generator extends Hookable {
	/**
	 *
	 * @param array $data
	 * @return boolean
	 */
	abstract public function validate(array $data);

	/**
	 * @return self
	 */
	abstract public function start();

	/**
	 * @param Instance $instance
	 * @return self
	 */
	abstract public function instance(Instance $instance);

	/**
	 * @param Site $site
	 * @return self
	 */
	abstract public function site(Site $site);

	/**
	 * @return self
	 */
	abstract public function finish();

	/**
	 * @return array
	 */
	abstract public function changed();

	/**
	 *
	 * @return self
	 */
	public function deploy(array $options = array()) {
		return $this;
	}

	/**
	 *
	 * @param string $file
	 * @param string $contents
	 * @return array
	 */
	protected function replace_file($file, $contents) {
		$disk_contents = File::contents($file, null);
		$compare_disk_contents = $this->call_hook("file_compare_preprocess", $disk_contents);
		$compare_contents = $this->call_hook("file_compare_preprocess", $contents);
		if ($compare_disk_contents === $compare_contents) {
			return array();
		}
		if ($this->option_bool("save_previous")) {
			File::unlink("$file.previous");
			if (is_file($file)) {
				rename($file, "$file.previous");
			}
		}
		if (!$this->option_bool("dry_run")) {
			File::put($file, $contents);
		}
		return array(
			$file => true,
		);
	}
}
