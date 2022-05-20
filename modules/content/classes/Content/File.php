<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 * @see Class_zesk\Content_File
 * @property integer $id
 * @property string $original
 * @property string $mime
 * @property Content_Data $data
 * @property string $description
 * @property User $user
 * @property Timestamp $created
 * @property Timestamp $modified
 */
class Content_File extends ORM {
	/**
	 * User-configurable settings
	 *
	 * @return multitype:multitype:string
	 */
	public static function settings() {
		return [
			'scan_path' => [
				'type' => 'list:path',
				'name' => 'List of internal file paths to scan for files to import.',
				'description' => 'File will be loaded and imported from this internal directory, once a minute via cron.',
			],
		];
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ORM::store()
	 */
	public function store(): self {
		if (!$this->mime) {
			$this->mime = MIME::from_filename($this->originalName);
		}
		return parent::store();
	}

	/**
	 * Retrieve path to file in file system.
	 * Creates file if doesn't exist locally.
	 * Use this with caution - for big files it can slow things down.
	 *
	 * @return string
	 */
	public function fullpath() {
		return $this->data->filepath();
	}

	/**
	 * fopen the file
	 *
	 * @param string $mode
	 * @return resource
	 */
	public function open_file($mode) {
		return $this->data->fopen($mode);
	}

	/**
	 * Copy the file to a destination
	 *
	 * @param string $destination
	 *        	Path to destination file
	 * @return boolean
	 */
	public function copy_file($destination) {
		return $this->data->copy_file($destination);
	}

	/**
	 * Does the destination file match our database version?
	 *
	 * @param string $destination
	 *        	Path to destination file
	 * @return boolean
	 */
	public function matches_file($destination) {
		return $this->data->matches_file($destination);
	}

	/**
	 * Return the size of the file
	 *
	 * @return integer
	 */
	public function size() {
		return $this->data->size();
	}

	/**
	 * Retrieve the download link for this file
	 *
	 * @param Response $response
	 * @return string
	 */
	public function download_link(Router $router) {
		return $router->get_route('download', __CLASS__, [
			'id' => $this->id(),
			'object' => $this,
		]);
	}

	/**
	 * Add extension given the mime type of the file for the file name
	 *
	 * @param string $name
	 *        	Name to add extension to
	 * @return string
	 */
	protected function add_extension($name) {
		$extension = MIME::to_extension($this->mime_type());
		if (!$extension) {
			return $name;
		}
		$actual_extension = File::extension($name, '', true);
		if ($actual_extension === $extension) {
			return $name;
		}
		return $name . '.' . $extension;
	}

	/**
	 * Download a file
	 *
	 * @param Response $response
	 * @param string $type
	 *        	Content-Disposition for download
	 * @return Response
	 */
	public function download(Response $response, $type = null) {
		return $response->download($this->fullpath(), $this->add_extension($this->name), $type);
	}

	/**
	 * original name of file
	 *
	 * @return string
	 */
	public function original_name() {
		return $this->original;
	}

	/**
	 * Mime Type of file
	 *
	 * @return string
	 */
	public function mime_type() {
		return $this->member('mime', 'application/unknown');
	}

	/**
	 * Given a file path, register a new file
	 *
	 * @param string $path
	 * @param boolean $copy
	 * @return \Content_File
	 */
	public function register_path($path, array $options = []) {
		$copy = $this->optionBool('scan_path_copy');
		$options = to_array($options);
		$data = Content_Data::copy_from_path($this->application, $path, $copy);

		$file = $this->application->object_fatcory(__CLASS__);
		$file->original = avalue($options, 'original', $path);
		if ($file->find([
			'original' => $file->original,
		])) {
			return $file;
		}
		$file->data = $data;
		$file->name = basename($path);

		return $file->store();
	}

	/**
	 * Load images from a particular directory and create Content_Files for them.
	 */
	public static function cron_minute(Application $application): void {
		$object = new Content_File($application);
		$paths = $object->option_list('scan_path');
		foreach ($paths as $path) {
			if (empty($path)) {
				continue;
			}
			if (!is_dir($path)) {
				$application->logger->error(__METHOD__ . ':=A configured path ({path}) was not found, <a href="{url}">please update the setting.</a>', [
					'path' => $path,
					'url' => 'admin/settings/Content_File::scan_path', /* TODO */
				]);

				continue;
			}
			/*
			 * Should probably have scan_path_copy settings on a per-dir basis TODO
			 */
			Directory::iterate($path, null, [
				$object,
				'register_path',
			]);
		}
	}

	/**
	 *
	 * @return string
	 */
	public function download_name() {
		$name = $this->original;
		if (!$name) {
			$name = $this->name;
		}
		$extension = '.' . MIME::to_extension($this->mime_type());
		return StringTools::unsuffix(basename($name), $extension) . $extension;
	}
}
