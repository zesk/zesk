<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Image_Library_imagick extends Image_Library {
	/**
	 *
	 * @var string
	 */
	public const command_default = 'convert';

	/**
	 *
	 * @var string
	 */
	public const command_scale = '{command} -antialias -matte -geometry "{width}x{height}" {source} {destination}';

	/**
	 *
	 * @return string
	 * @throws Exception_NotFound
	 */
	private function shellCommand(): string {
		$command = $this->application->configuration->getPath([
			__CLASS__, 'command',
		], self::command_default);
		return $this->application->paths->which($command);
	}

	/**
	 *
	 * @return string
	 * @throws Exception_NotFound
	 */
	private function shellCommandScale(): string {
		$command = $this->shellCommand();
		$pattern = $this->application->configuration->getPath([
			__CLASS__, 'command_scale',
		], self::command_scale);
		return map($pattern, [
			'command' => $command,
		]);
	}

	/**
	 *
	 * @return boolean
	 */
	public function installed(): bool {
		try {
			$this->shellCommand();
			return true;
		} catch (Exception_NotFound) {
			return false;
		}
	}

	/**
	 * @param string $data
	 * @param array $options
	 * @return string
	 * @throws Exception_Command
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_NotFound
	 */
	public function imageScaleData(string $data, array $options): string {
		$extension = Content_Image::determine_extension_simple_data($data);
		$source = File::temporary($this->application->paths->temporary(), $extension);
		$dest = File::temporary($this->application->paths->temporary(), $extension);
		file_put_contents($source, $data);
		$result = null;
		if ($this->imageScale($source, $dest, $options)) {
			$result = file_get_contents($dest);
		}
		unlink($source);
		unlink($dest);
		return $result;
	}

	/**
	 * @param string $source
	 * @param string $dest
	 * @param array $options
	 * @return bool
	 * @throws Exception_Command
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_NotFound
	 */
	public function imageScale(string $source, string $dest, array $options): bool {
		File::depends($source);
		Directory::must(dirname($dest));
		[$actual_width, $actual_height] = getimagesize($source);
		$width = $options['width'] ?? $actual_width;
		$height = $options['height'] ?? $actual_height;

		$map = [
			'source' => escapeshellarg($source), 'destination' => escapeshellarg($dest), 'width' => $width,
			'height' => $height,
		];

		$cmd = $this->shellCommandScale();
		$cmd = map($cmd, $map);

		try {
			$this->application->process->executeArguments($cmd);
			if (file_exists($dest)) {
				chmod($dest, /* 0o644 */ 420);
				$this->application->hooks->call('file_created', $dest);
				return true;
			}
		} catch (\Exception $e) {
			File::unlink($dest);

			throw $e;
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param float $degrees
	 * @param array $options
	 * @return bool
	 * @throws Exception_Unimplemented
	 */
	public function imageRotate(string $source, string $destination, float $degrees, array $options = []): bool {
		throw new Exception_Unimplemented('TODO');
	}
}
