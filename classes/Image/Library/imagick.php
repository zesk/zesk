<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

class Image_Library_imagick extends Image_Library {
	/**
	 *
	 * @var string
	 */
	const command_default = "convert";

	/**
	 *
	 * @var string
	 */
	const command_scale = '{command} -antialias -matte -geometry "{width}x{height}" {source} {destination}';

	/**
	 *
	 * @return string|\zesk\NULL
	 */
	private static function shell_command() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$which = $zesk->paths->which($zesk->configuration->path_get(array(
			__CLASS__,
			"command"
		), self::command_default));
		return $which;
	}

	/**
	 *
	 * @return string|\zesk\NULL
	 */
	private static function shell_command_scale() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$command = self::shell_command();
		$scale_command = map($zesk->configuration->path_get(array(
			__CLASS__,
			"command_scale"
		), self::command_scale), compact("command"));
		return $scale_command;
	}

	/**
	 *
	 * @return boolean
	 */
	public static function installed() {
		$which = self::shell_command();
		if ($which) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param unknown $source
	 * @return resource
	 */
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
		$type = MIME::from_filename($dest);
		$output = avalue(self::$output_map, $type, 'png');
		$method = "image$output";
		return $method($dst, $dest);
	}
	function image_scale_data($data, array $options) {
		$extension = Content_Image::determine_extension_simple_data($data);
		$source = File::temporary($this->application->paths->temporary(), $extension);
		$dest = File::temporary($this->application->paths->temporary(), $extension);
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

		$lastline = system($cmd, $result);
		if ($result == 0 && file_exists($dest)) {
			global $zesk;
			@chmod($dest, 0644);
			$zesk->hooks->call('file_created', $dest);
			return true;
		}
		die("ViewImage::scaleCommand: System command failed \"$cmd\" returned $result ($lastline)");
	}
	function image_rotate($source, $destination, $degrees, array $options = array()) {
		throw new Exception_Unimplemented("TODO");
	}
}
