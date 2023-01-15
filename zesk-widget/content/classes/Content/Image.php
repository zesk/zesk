<?php
declare(strict_types=1);

/**
 * @package zesk-lite
 * @subpackage image
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @see Class_Content_Image
 * @see Content_Image
 * @author kent
 * @property integer $id
 * @property integer $parent
 * @property Content_Data $data
 * @property string $title
 * @property string $description
 * @property string $mime_type
 * @property string $path
 * @property integer $width
 * @property integer $height
 */
class Content_Image extends ORMBase {
	/**
	 * Register from a known file - will copy to database
	 *
	 * @param string $path
	 * @param array $members
	 * @return Content_Image
	 * @throws Exception_Invalid
	 */
	public static function registerFromFile(Application $application, $path, array $members = [], $copy = true, $register = true) {
		File::depends($path);
		$members['data'] = $cf = Content_Data::from_path($application, $path, $copy, true);
		if (!array_key_exists('path', $members)) {
			$ext = self::determine_extension_simple($path);
			if (!$ext) {
				throw new Exception_Invalid('Not an image file {path}', compact('path'));
			}
			$members['path'] = basename($path);
		}
		$image = $application->ormFactory(__CLASS__, $members);
		return $register ? $image->register() : $image->store();
	}

	/**
	 *
	 * @return boolean
	 */
	public function is_portrait() {
		return $this->height > $this->width;
	}

	/**
	 *
	 * @return boolean
	 */
	public function is_landscape() {
		return $this->width > $this->height;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORMBase::fetchObject()
	 */
	protected function fetchObject() {
		$query = $this->querySelect('X');
		$get_data = false;
		if ($get_data) {
			$query->link('Content_Data', [
				'required' => false,
				'path' => 'data',
			]);
		}
		$query->addWhere('X.' . $this->idColumn(), $this->id());
		$query->ormWhat(__CLASS__, null, 'image_');
		if ($get_data) {
			$query->ormWhat('Content_Data', null, 'data_');
		}

		$result = $query->one();
		if (!$result) {
			return [];
		}
		$add = [];
		if ($get_data) {
			$add = [
				'data' => $this->ormFactory('Content_Data')->initialize(ArrayTools::keysRemovePrefix($result, 'data_', true), true),
			];
		}
		return $add + ArrayTools::keysRemovePrefix($result, 'image_', true);
	}

	/**
	 * Force files to disk
	 */
	public function sync(): void {
		$this->_force_to_disk();
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ORMBase::store()
	 */
	public function store(): self {
		$this->_update_sizes();
		if ($this->memberIsEmpty('mime_type')) {
			$this->mime_type = MIME::fromExtension($this->path);
		}
		return parent::store();
	}

	/**
	 * Does the file exist on the system? Useful if you are running in a cluster.
	 *
	 * @return boolean
	 */
	public function file_exists($force = false) {
		if ($force) {
			$this->_force_to_disk();
		}
		return file_exists($this->path());
	}

	/**
	 * Returns the path, minus the docroot
	 */
	public function source() {
		return StringTools::removePrefix($this->path(), $this->application->documentRoot());
	}

	/**
	 * Path to where image are stored
	 *
	 * @return string
	 */
	public function content_image_path() {
		return $this->option('path', path($this->application->documentRoot(), 'cache/images'));
	}

	/**
	 * Given an internal path and a raw data file, fix the internal path extension to make it a
	 * servable name
	 *
	 * @param string $path
	 *            Relative path of file
	 * @param unknown $rawfile
	 * @return unknown Ambigous unknown>
	 */
	private function fix_extension($path, $rawfile) {
		if (MIME::fromExtension($path) === $this->mime_type) {
			return $path;
		}
		$extension = self::determine_extension($rawfile);
		return $extension ? File::setExtension($path, $extension) : $path;
	}

	/**
	 *
	 * @return string
	 */
	public function basename() {
		return basename($this->path);
	}

	/**
	 * Retrieve the path for this image
	 *
	 * @return string
	 */
	public function path() {
		return path($this->content_image_path(), $this->member('path'));
	}

	/**
	 * Retrieve the file size in bytes of this image
	 *
	 * @return number
	 */
	public function size() {
		return $this->data->size();
	}

	/**
	 * Simplistic test to see if a file is of a particular type.
	 * Works great on validly formatted images.
	 *
	 * Not-so-great on junk data. Returns image type:
	 *
	 * * gif
	 * * jpg
	 * * png
	 * * swf
	 *
	 * Or false if not able to determine image type.
	 *
	 * @param string $data
	 *            Raw image data
	 * @return string|boolean
	 * @see Content_Image::determine_extension_simple
	 */
	public static function determine_extension_simple_data($data) {
		$head = substr($data, 0, 12);
		$head = trim(strtolower(preg_replace('/[^A-Za-z]/', '', $head)));

		if (substr($head, 0, 3) == 'gif') {
			return 'gif';
		}
		if (substr($head, 0, 4) == 'jfif') {
			return 'jpg';
		}
		if (substr($head, 0, 3) == 'png') {
			return 'png';
		}
		if (substr($head, 0, 3) == 'cws') {
			return 'swf';
		}

		return false;
	}

	/**
	 * Simple file extension determination for image files
	 *
	 * @param string $filename
	 * @return string or false if failed
	 */
	public static function determine_extension_simple($filename) {
		if (empty($filename)) {
			return false;
		}
		return self::determine_extension_simple_data(file_get_contents($filename, null, null, 0, 12));
	}

	/**
	 * Determine file extension for file using exif
	 *
	 * @param string $filename
	 * @return string boolean if not able to determine it.
	 */
	public static function determine_extension($filename) {
		if (empty($filename)) {
			return false;
		}
		if (!function_exists('exif_imagetype')) {
			return self::determine_extension_simple($filename);
		}

		$t = exif_imagetype($filename);
		if (!$t) {
			return false;
		}
		$t2ext = [
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_SWF => 'swf',
		];
		//			IMAGETYPE_PSD => "psd",
		//			IMAGETYPE_BMP => "bmp",
		//			IMAGETYPE_TIFF_II => "tiff",
		//			IMAGETYPE_TIFF_MM => "tiff",
		//			IMAGETYPE_JPC => "jpc",
		//			IMAGETYPE_JP2 => "jp2",
		//			IMAGETYPE_JPX => "jpx",

		if (isset($t2ext[$t])) {
			return $t2ext[$t];
		}
		return false;
	}

	/**
	 * Copy file to disk where it should be
	 *
	 * @return boolean
	 */
	private function _force_to_disk() {
		if ($this->file_exists()) {
			return true;
		}
		$data = $this->data;
		if (!$data) {
			$this->application->logger->warning('{class} {id} has empty or missing data "{data}"', [
				'class' => get_class($this),
				'id' => $this->id(),
				'data' => $this->memberInteger('data'),
			]);
			return false;
		}
		if ($data->matches_file($this->path())) {
			return true;
		}
		$path = $this->path();
		$dir = dirname($path);
		Directory::depend($dir);

		try {
			$result = $data->copy_file($path);
		} catch (Exception_File_NotFound $e) {
			return false;
		}
		$fixed_path = $this->fix_extension($this->path, $path);
		if ($fixed_path !== $this->path) {
			$this->path = $fixed_path;
			$fixed_path = $this->path();
			if (!rename($path, $fixed_path)) {
				return false;
			}
			if (!$this->store()) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Update Width and Height
	 *
	 * @return boolean
	 */
	private function _update_sizes() {
		if (!$this->memberIsEmpty('width') && !$this->memberIsEmpty('height')) {
			return true;
		}
		$this->_force_to_disk();
		$path = $this->path();
		if (empty($path)) {
			return false;
		}
		$result = @getimagesize($path);
		if (!is_array($result)) {
			trigger_error(__CLASS__ . "::_update_sizes(): getimagesize failed on \"$path\"" . _backtrace(), E_USER_WARNING);
			return false;
		}
		$this->setMember('width', $result[0]);
		$this->setMember('height', $result[1]);
		return true;
	}

	/**
	 * Rotate image $degrees degrees in one direction or the other
	 *
	 * @param int $degrees
	 * @return boolean
	 */
	public function rotate($degrees) {
		$this->_force_to_disk();
		$path = $this->path();
		$result = Image_Library::factory($this->application)->imageRotate($path, $path, $degrees);
		if (!$result) {
			return null;
		}
		$this->data = Content_Data::from_path($this->application, $path, true, true);
		$this->_update_sizes();
		return $this->store();
	}

	/**
	 * Returns an array of (width, height) with the new image constrained the the box size.
	 *
	 * Image result width is guaranteed to be <= $width
	 * Image result height is guaranteed to be <= $height
	 *
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	public function constrainDimensions($width, $height) {
		return Image_Library::constrainDimensions($this->width, $this->height, $width, $height);
	}

	/**
	 * Given an image file, fix the orientation based on the EXIF Orientation data
	 *
	 * Returns true if file was fixed or unchanged, "unsupported" if operation is not supported, and
	 * "failed" if conversion failed
	 *
	 * @param string $file
	 * @return NULL|string|boolean
	 */
	public static function correct_orientation(Application $application, $file) {
		if (!function_exists('exif_read_data')) {
			return null;
		}
		$exif = @exif_read_data($file);
		if (!is_array($exif)) {
			return 'unsupported';
		}
		$orientation = $exif['Orientation'] ?? 'none';
		$rotate = null;
		switch ($orientation) {
			case 'none':
				return true;
			case 3:
				$rotate = 180;

				break;
			case 6:
				$rotate = 270;

				break;
			case 8:
				$rotate = 90;

				break;
		}
		if ($rotate === null) {
			return false;
		}
		$result = Image_Library::factory($application)->imageRotate($file, $file, $rotate);
		if (!$result) {
			return 'failed';
		}
		return true;
	}

	/**
	 */
	public static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__);
	}

	/**
	 *
	 * @param User $user
	 * @param Permission $perm
	 * @return mixed|boolean
	 */
	public function hook_permission(User $user, Permission $perm) {
		$is_mine = toBool($this->memberQuery('users')->addWhere('users.id', $user)->what('*n', 'COUNT(users.id)')->integer('n'));
		return $is_mine;
	}

	/**
	 * For all images in the database, convert them all so they are all reduced per options passed in.
	 *
	 * This is destructive and can be very damaging to your content if you wish to retain full-sized images.
	 *
	 * Options are:
	 *
	 * "size" - Max file size
	 * "width" - Scale to this maximum width
	 * "height" - Scale to this maximum height
	 * "delete" - boolean value. Whether to delete old Content_Data.
	 *
	 * @param Application $application
	 * @param int $theshold
	 * @see self::reduce_image_dimensions
	 */
	public static function downscale_images(Application $application, array $options): void {
		$query = $application->ormRegistry(__CLASS__)->querySelect('X');
		$query->ormWhat(__CLASS__, 'X', 'image_');
		$query->link(Content_Data::class, [
			'alias' => 'D',
		]);
		$query->ormWhat(Content_Data::class, 'D', 'data_');
		$query->addWhere('D.type', 'path');
		$size = $options['size'] ?? null;
		if ($size) {
			$query->addWhere('D.size|>=', $size);
		}

		$iterator = $query->ormIterators();
		foreach ($iterator as $row) {
			/* @var $image Content_Image */
			/* @var $data Content_Data */
			$image = $row['X'];
			$data = $row['D'];

			try {
				$image->reduce_image_dimensions($options);
			} catch (Exception_File_Format $e) {
				$application->logger->warning('{class} #{id} - {path} - {data_md5hash} Invalid image format', [
					'class' => $image::class,
					'id' => $image->id(),
					'path' => $image->path,
					'data_md5hash' => $data->md5hash,
				]);
			}
		}
	}

	/**
	 * Options are:
	 *
	 * "size" - Max file size
	 * "width" - Scale to this maximum width
	 * "height" - Scale to this maximum height
	 * "delete" - boolean value. Whether to delete old Content_Data.
	 *
	 * If all three
	 * @param array $options
	 * @return NULL
	 */
	public function reduce_image_dimensions(array $options) {
		$path = $this->path();
		$__ = [
			'id' => $this->id(),
			'class' => get_class($this),
			'data_id' => $this->memberInteger('data'),
			'path' => basename($path),
			'data_name' => $this->data->md5hash,
		];
		if (!$this->file_exists(true)) {
			$this->application->logger->info('{class} #{id}: {path}: No file found {data_name}', $__);
			return $this;
		}
		$scaled = $path . '.scaled';
		$size = filesize($path);

		$this->_update_sizes();

		$maximum_file_size = $options['size'] ?? null;
		$maximum_width = $options['width'] ?? null;
		$maximum_height = $options['height'] ?? null;
		$__ += [
			'width' => $maximum_width,
			'height' => $maximum_height,
			'max_size' => $maximum_file_size,
			'size' => $size,
		];

		if ($maximum_file_size === null && $maximum_width === null && $maximum_height === null) {
			throw new Exception_Parameter('{method}: Parameter $options must contain one of keys: size, width, height', [
				'method' => __METHOD__,
			]);
		}
		$new_width = $width = $this->width;
		$new_height = $height = $this->height;

		$__ = [
			'id' => $this->id(),
			'class' => get_class($this),
			'data_id' => $this->memberInteger('data'),
			'width' => $width,
			'height' => $height,
			'max_size' => $maximum_file_size,
			'size' => $size,
			'path' => basename($path),
		];

		if (is_numeric($maximum_file_size)) {
			$ratio = $maximum_file_size / $size;

			$new_width = round($width * $ratio);
			$new_height = round($height * $ratio);
		}

		if ($maximum_width !== null) {
			if ($new_width > $maximum_width) {
				$new_height = $new_height * ($maximum_width / $new_width);
				$new_width = $maximum_width;
			}
		}
		if ($maximum_height !== null) {
			if ($new_height > $maximum_height) {
				$new_width = $new_width * ($maximum_height / $new_height);
				$new_height = $maximum_height;
			}
		}
		if ($new_width === $width && $new_height === $height) {
			$this->application->logger->info('{class} #{id}: {path}: Size unchanged', $__);
			return $this;
		}
		$__ += [
			'new_width' => $new_width,
			'new_height' => $new_height,
		];
		$imageTool = Image_Library::factory($this->application);

		try {
			if (!$imageTool->imageScale($path, $scaled, [
				'width' => $new_width,
				'height' => $new_height,
			])) {
				$this->application->logger->error('{class} #{id} Unable to scale {path} (data {data_id}) to {new_width}x{new_height}', $__);

				throw new Exception_Convert('{class} #{id} Unable to scale {path} (data {data_id}) to {new_width}x{new_height}', $__);
			}
		} catch (Exception_Semantics $e) {
			throw new Exception_File_Format($path, 'Invalid image format');
			// zesk\Image_Library_GD::_imageload passed an invalid string of 14103036 bytes
		}
		$__['new_size'] = $new_size = filesize($scaled);
		$__['percent'] = sprintf('%0.2f%%', ($new_size / $size) * 100);
		$__['status'] = ($new_size < $maximum_file_size) ? 'OK' : 'FAILED';

		$this->application->logger->info('{class} #{id}: {path}: {percent} new size {status} {size} bytes ({width}x{height}) => {new_size} bytes ({new_width}x{new_height}) (MAX: {max_size})', $__);

		$old_data = $this->data;
		$new_data = Content_Data::from_path($this->application, $scaled, false, true);
		$this->data = $new_data;
		$result = $this->store();
		if ($result) {
			if ($options['delete'] ?? null) {
				$this->application->logger->notice('{class} #{id}: {path}: {percent} REPLACED Data: {new_data_id}, Deleting old image data: {data_id}', $__);
				$old_data->delete();
			} else {
				$this->application->logger->info('{class} #{id}: {path}: {percent} REPLACED Data: {new_data_id}, Previous: {data_id}', $__ + [
					'new_data_id' => $new_data->id(),
				]);
			}
		}
		return $result;
	}
}
