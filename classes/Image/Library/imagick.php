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
	private function shell_command() {
		$command = $this->application->configuration->path_get(array(
			__CLASS__,
			"command"
		), self::command_default);
		$which = $this->application->paths->which($command);
		if (!$which) {
			throw new Exception_Configuration(array(
				__CLASS__,
				"command"
			), "Command {command} not found in path {paths}", array(
				"command" => $command,
				"paths" => $this->application->paths->command()
			));
		}
		return $which;
	}
	
	/**
	 *
	 * @return string|\zesk\NULL
	 */
	private function shell_command_scale() {
		$command = $this->shell_command();
		$pattern = $this->application->configuration->path_get(array(
			__CLASS__,
			"command_scale"
		), self::command_scale);
		$scale_command = map($pattern, array(
			"command" => $command
		));
		if (empty($scale_command)) {
			throw new Exception_Configuration(array(
				__CLASS__,
				"command_scale"
			), "Is an empty string?");
		}
		return $scale_command;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function installed() {
		$which = $this->shell_command();
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
		
		$cmd = $this->shell_command_scale();
		$cmd = map($cmd, $map);
		try {
			$lines = $this->application->process->execute_arguments($cmd);
			if (file_exists($dest)) {
				@chmod($dest, 0644);
				$this->application->hooks->call('file_created', $dest);
				return true;
			}
		} catch (\Exception $e) {
			if (file_exists($dest)) {
				@unlink($dest);
			}
			throw $e;
		}
	}
	function image_rotate($source, $destination, $degrees, array $options = array()) {
		throw new Exception_Unimplemented("TODO");
	}
}
