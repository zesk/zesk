<?php
class Server_Files_Direct extends Server_Files {
	function is_file($file) {
		return is_file($file);
	}
	function is_dir($dir) {
		return is_dir($dir);
	}
	function mkdir($pathname, $mode = null, $recursive = true) {
		return @mkdir($pathname, $mode, $recursive);
	}
	function chmod($path, $mode) {
		return @chmod($path, $mode);
	}
	function stat($path, $section = null) {
		return file::stat($path, $section);
	}
	function file_put_contents($path, $contents) {
		return file_put_contents($path, $contents);
	}
	function copy($source, $dest) {
		return copy($source, $dest);
	}
	function file_get_contents($path) {
		return file_get_contents($path);
	}
	function file_exists($source) {
		return file_exists($source);
	}
	function md5_file($file) {
		return md5_file($file);
	}
}
