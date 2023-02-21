<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Image\Library;

use zesk\Directory;
use zesk\Exception\CommandFailed;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\Unimplemented;
use zesk\File;
use zesk\Image\Library;

class ImageMagick extends Library {
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
	 * @throws NotFoundException
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
	 * @throws NotFoundException
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
		} catch (NotFoundException) {
			return false;
		}
	}

	/**
	 * @param string $data
	 * @param array $options
	 * @return string
	 * @throws CommandFailed
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws NotFoundException
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
	 * @throws CommandFailed
	 * @throws DirectoryNotFound
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws NotFoundException
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
			return false;
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
	 * @throws Unimplemented
	 */
	public function imageRotate(string $source, string $destination, float $degrees, array $options = []): bool {
		throw new Unimplemented('TODO');
	}
}
