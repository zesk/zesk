<?php declare(strict_types=1);
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
	public function deploy(array $options = []) {
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
		$file_exists = ($disk_contents === null) ? false : true;

		$compare_disk_contents = $this->call_hook("file_compare_preprocess", $disk_contents);
		$compare_contents = $this->call_hook("file_compare_preprocess", $contents);
		if ($compare_disk_contents === $compare_contents) {
			// If our file exists and the replacement is empty, make sure we drop through to delete the file
			if ($file_exists && $contents === "") {
				// Fall through and delete the file
			} else {
				return [];
			}
		}
		if ($this->optionBool("save_previous")) {
			File::unlink("$file.previous");
			if (is_file($file)) {
				rename($file, "$file.previous");
			}
		}
		if (!$this->optionBool("dry_run")) {
			if (!empty($contents)) {
				File::put($file, $contents);
			} else {
				File::unlink($file);
			}
		}
		return [
			$file => true,
		];
	}
}
