<?php
declare(strict_types=1);

namespace zesk\Request;

use zesk\Application;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\ParameterException;
use zesk\File as zeskFile;

class File
{
	/**
	 *
	 * @var string
	 */
	private string $tmp_name;

	/**
	 *
	 * @var string
	 */
	private string $name;

	/**
	 *
	 * @var string
	 */
	private string $tmp_path;

	/**
	 * @var string
	 */
	private string $ext;

	/**
	 *
	 * @var array
	 */
	private array $upload_array = [];

	/**
	 *
	 * @param array $upload_array
	 * @throws FilePermission
	 * @throws ParameterException
	 */
	public function __construct(array $upload_array)
	{
		if (!isset($upload_array['tmp_name'])) {
			throw new ParameterException('{method} must have keys tmp_name (keys passed: {keys})', [
				'method' => __METHOD__, 'keys' => array_keys($upload_array),
			]);
		}
		$this->upload_array = $upload_array;
		$this->tmp_path = $upload_array['tmp_name'];
		if (!is_uploaded_file($this->tmp_path)) {
			throw new FilePermission($this->tmp_path, 'Not an uploaded file');
		}
		$this->name = $upload_array['name'] ?? basename($this->tmp_path);
		$this->ext = zeskFile::extension($this->name);
	}

	/**
	 * @return array
	 */
	public function variables(): array
	{
		return $this->upload_array;
	}

	/**
	 * Create a new instance
	 *
	 * @param array $upload_array
	 * @return self
	 * @throws FilePermission
	 * @throws ParameterException
	 */
	public static function instance(array $upload_array): self
	{
		return new self($upload_array);
	}

	/**
	 * Process upload files before anyone else
	 */
	public const FILTER_UPLOAD = self::class . '::upload';

	/**
	 * Sample options are:
	 *
	 *     array(
	 *       "hash" => true,       // Move the file to the target path but rename it based on the hash
	 *       "dir_mode" => 0775,   // Set the directory mode of the target path to this directory mode upon creation ONLY
	 *       "file_mode" => 0664,  // Set the file mode of the target file to this file mode ALWAYS
	 *       "skip_hook" => true,  // Do not call the "upload" hook with the new file name
	 *     )
	 *
	 * @param string $dest_path
	 * @param array $options
	 * @return string
	 * @throws ParameterException
	 */
	/**
	 * @param Application $application
	 * @param string $dest_path
	 * @param array $options
	 * @return string
	 * @throws ParameterException
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound|FilePermission
	 */
	public function migrate(Application $application, string $dest_path, array $options = []): string
	{
		if (empty($dest_path)) {
			throw new ParameterException('$dest_path is required to be a valid path or filename ({dest_path})', [
				'dest_path' => $dest_path,
			]);
		}

		$dest_dir = is_dir($dest_path) ? $dest_path : dirname($dest_path);

		Directory::depend($dest_dir, $options['dir_mode'] ?? 0o700);

		if ($options['hash'] ?? false) {
			$x = md5_file($this->tmp_path);
			$dest_path = Directory::path($dest_dir, "$x." . $this->ext);
		}
		move_uploaded_file($this->tmp_path, $dest_path);
		if ($options['file_mode'] ?? false) {
			zeskFile::chmod($dest_path, $options['file_mode']);
		}
		if (!($options['skip_hook'] ?? false)) {
			$dest_path = $application->invokeTypedFilters(self::FILTER_UPLOAD, $dest_path, [$application]);
		}
		return $dest_path;
	}
}
