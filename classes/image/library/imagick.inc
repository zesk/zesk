<?php
class Image_Library_Imagick extends Image_Library {
	const command_scale = '{command} -antialias -matte -geometry "{width}x{height}" {source} {destination}';

	/**
	 *
	 * @return boolean
	 */
	public static function installed() {
		return is_executable(zesk::get(''));
	}
	private function _imageload($source) {
		return imagecreatefromstring(file_get_contents($source));
	}
	private function _imagecreate($width, $height) {
		if (!$res = @imagecreatetruecolor($width, $height)) {
			$res = imagecreate($width, $height);
		}
		return $res;
	}
	private function _imageoutput($dst, $dest) {
		$type = mime::from_filename($dest);
		$output = avalue(self::$output_map, $type, 'png');
		$method = "image$output";
		return $method($dst, $dest);
	}
	private static function shell_command_scale() {
		static $command = null;
		if (!$command) {
			$keys = to_list('Image_Library_Imagick::command');
			$command = zesk::get1($keys);
			if (!$command) {
				$command = zesk::which('convert');
			}
		}
		return map(self::command_scale, compact('command'));
	}
	function image_scale_data($data, array $options) {
		$extension = Content_Image::determine_extension_simple_data($data);
		$source = file::temporary($extension);
		$dest = file::temporary($extension);
		file_put_contents($source, $data);
		$result = null;
		if (self::image_scale($source, $dest, $options)) {
			$result = file_get_contents($dest);
		}
		unlink($source);
		unlink($dest);
		return $result;
	}
	function image_scale($source, $dest, array $options) {
		list($actual_width, $actual_height) = getimagesize($source);
		$width = $actual_width;
		$height = $actual_height;
		extract($options, EXTR_IF_EXISTS);
		if (avalue($_SERVER, 'WINDIR')) {
			$win_path = str_replace('/', '\\', dirname($dest));
			if (!@mkdir($win_path, 0770, true)) {
				die("can't make directory $win_path");
			}
			copy($source, $dest);
			return true;
		}
		$map = array(
			"source" => escapeshellarg($source),
			"destination" => escapeshellarg($dest),
			"width" => $width,
			"height" => $height
		);

		$cmd = self::shell_command_scale();
		if (empty($cmd)) {
			die("ViewImage::scaleCommand($source, $dest, $width, $height): System doesn't contain ViewImage::system_scale_command");
		}

		$cmd = map($cmd, $map);

		$result = false;
		$this->debug_log("Running command: $cmd");

		$lastline = system($cmd, $result);
		if ($result == 0 && file_exists($dest)) {
			@chmod($dest, 0644);
			zesk::hook('file_created', $dest);
			return true;
		}
		die("ViewImage::scaleCommand: System command failed \"$cmd\" returned $result ($lastline)");
	}
	function image_rotate($source, $destination, $degrees, array $options = array()) {
		throw new Exception_Unimplemented("TODO");
	}
}