<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server\Files;

use Server\classes\Server\Server_Files;
use zesk\File;

/**
 *
 * @author kent
 *
 */
class Server_Files_Direct extends Server_Files {
	public function is_file($file) {
		return is_file($file);
	}

	public function is_dir($dir) {
		return is_dir($dir);
	}

	public function mkdir($pathname, $mode = null, $recursive = true) {
		return @mkdir($pathname, $mode, $recursive);
	}

	public function chmod($path, $mode) {
		return @chmod($path, $mode);
	}

	public function stat($path, $section = null) {
		return File::stat($path, $section);
	}

	public function file_put_contents($path, $contents) {
		return file_put_contents($path, $contents);
	}

	public function copy($source, $dest) {
		return copy($source, $dest);
	}

	public function file_get_contents($path) {
		return file_get_contents($path);
	}

	public function file_exists($source) {
		return file_exists($source);
	}

	public function md5_file($file) {
		return md5_file($file);
	}
}
