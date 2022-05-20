<?php declare(strict_types=1);
namespace zesk\Request;

use zesk\Application;
use zesk\File as zeskFile;
use zesk\Directory;
use zesk\Exception_File_Permission;
use zesk\Exception_Parameter;

class File {
	/**
	 *
	 * @var string
	 */
	private $tmp_name = null;

	/**
	 *
	 * @var string
	 */
	private $name = null;

	/**
	 *
	 * @var string
	 */
	private $ext = null;

	/**
	 *
	 * @var array
	 */
	private $upload_array = [];

	/**
	 *
	 * @param array $upload_array
	 * @throws Exception_File_Permission
	 */
	public function __construct(array $upload_array) {
		if (!isset($upload_array['tmp_name'])) {
			throw new Exception_Parameter('{method} must have keys tmp_name (keys passed: {keys})', [
				'method' => __METHOD__,
				'keys' => array_keys($upload_array),
			]);
		}
		$this->upload_array = $upload_array;
		$this->tmp_path = $upload_array['tmp_name'];
		if (!is_uploaded_file($this->tmp_path)) {
			throw new Exception_File_Permission($this->tmp_path, 'Not an uploaded file');
		}
		$this->name = avalue($upload_array, 'name', basename($this->tmp_path));
		$this->ext = zeskFile::extension($this->name);
	}

	/**
	 * Create a new instance
	 *
	 * @param array $upload_array
	 * @return \zesk\Request\File
	 */
	public static function instance(array $upload_array) {
		return new self($upload_array);
	}

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
	 * @throws Exception_Parameter
	 * @return string
	 */
	public function migrate(Application $application, $dest_path, array $options = []) {
		if (empty($dest_path)) {
			throw new Exception_Parameter('$dest_path is required to be a valid path or filename ({dest_path})', [
				'dest_path' => $dest_path,
			]);
		}

		$dest_dir = is_dir($dest_path) ? $dest_path : dirname($dest_path);

		Directory::depend($dest_dir, avalue($options, 'dir_mode'));

		if (avalue($options, 'hash')) {
			$x = md5_file($this->tmp_path);
			$dest_path = path($dest_dir, "$x." . $this->ext);
		}
		move_uploaded_file($this->tmp_path, $dest_path);
		if (avalue($options, 'file_mode')) {
			@chmod($dest_path, avalue($options, 'file_mode'));
		}
		if (!avalue($options, 'skip_hook')) {
			$application->hooks->call('upload', $dest_path);
		}
		return $dest_path;
	}
}
