<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/zesk/content/image.inc $
 * @package zesk-lite
 * @subpackage image
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Directory;
use \Content_Data;
use \Image_Library;

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
class Content_Image extends Object {
	/**
	 * Register from a known file - will copy to database
	 *
	 * @param string $path
	 * @param array $members
	 * @throws Exception_Invalid
	 * @return Content_Image
	 */
	public static function register_from_file($path, array $members = array(), $copy = true, $register = true) {
		file::depends($path);
		$members['data'] = $cf = Content_Data::from_path($path, $copy, true);
		if (!array_key_exists("path", $members)) {
			$ext = self::determine_extension_simple($path);
			if (!$ext) {
				throw new Exception_Invalid("Not an image file {path}", compact("path"));
			}
			$members['path'] = basename($path);
		}
		$image = new Content_Image($members);
		return $register ? $image->register() : $image->store();
	}
	public function is_portrait() {
		return $this->height > $this->width;
	}
	public function is_landscape() {
		return $this->width > $this->height;
	}
	protected function fetch_object() {
		$query = $this->query_select("X");
		$get_data = false;
		if ($get_data) {
			$query->link('Content_Data', array(
				'required' => false,
				'path' => 'data'
			));
		}
		$query->where("X." . $this->id_column(), $this->id());
		$query->what_object("Content_Image", null, "image_");
		if ($get_data) {
			$query->what_object("Content_Data", null, "data_");
		}
		
		$result = $query->one();
		if (!$result) {
			return array();
		}
		$add = array();
		if ($get_data) {
			$add = array(
				'data' => $this->object_factory('Content_Data')->initialize(arr::kunprefix($result, "data_", true), true)
			);
		}
		return $add + arr::kunprefix($result, "image_", true);
	}
	/**
	 * Force files to disk
	 */
	public function sync() {
		$this->_force_to_disk();
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Object::store()
	 */
	function store() {
		$this->_update_sizes();
		if ($this->member_is_empty("mime_type")) {
			$this->mime_type = mime::from_filename($this->path);
		}
		return parent::store();
	}
	
	/**
	 * Does the file exist on the system? Useful if you are running in a cluster.
	 *
	 * @return boolean
	 */
	function file_exists($force = false) {
		if ($force) {
			$this->_force_to_disk();
		}
		return file_exists($this->path());
	}
	
	/**
	 * Returns the path, minus the docroot
	 */
	function source() {
		return str::unprefix($this->path(), $this->application->document_root());
	}
	
	/**
	 * Path to where image are stored
	 *
	 * @return string
	 */
	function content_image_path() {
		return $this->option("path", path($this->application->document_root(), "cache/images"));
	}
	
	/**
	 * Given an internal path and a raw data file, fix the internal path extension to make it a
	 * servable name
	 *
	 * @param string $path
	 *        	Relative path of file
	 * @param unknown $rawfile
	 * @return unknown Ambigous unknown>
	 */
	private function fix_extension($path, $rawfile) {
		if (MIME::from_filename($path) === $this->mime_type) {
			return $path;
		}
		$extension = self::determine_extension($rawfile);
		return $extension ? File::extension_change($path, $extension) : $path;
	}
	function basename() {
		return basename($this->path);
	}
	/**
	 * Retrieve the path for this image
	 *
	 * @return string
	 */
	function path() {
		return path($this->content_image_path(), $this->member("path"));
	}
	
	/**
	 * Retrieve the file size in bytes of this image
	 *
	 * @return number
	 */
	function size() {
		return $this->data->size();
	}
	
	/**
	 * Simplistic test to see if a file is of a particular type. Works great on validly formatted images.
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
	 * @see Content_Image::determine_extension_simple
	 * @param string $data Raw image data
	 * @return string|boolean
	 */
	static public function determine_extension_simple_data($data) {
		$head = substr($data, 0, 12);
		$head = trim(strtolower(preg_replace("/[^A-Za-z]/", "", $head)));
		
		if (substr($head, 0, 3) == "gif") {
			return "gif";
		}
		if (substr($head, 0, 4) == "jfif") {
			return "jpg";
		}
		if (substr($head, 0, 3) == "png") {
			return "png";
		}
		if (substr($head, 0, 3) == "cws") {
			return "swf";
		}
		
		return false;
	}
	/**
	 * Simple file extension determination for image files
	 *
	 * @param string $filename
	 * @return string or false if failed
	 */
	static public function determine_extension_simple($filename) {
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
	static public function determine_extension($filename) {
		if (empty($filename)) {
			return false;
		}
		if (!function_exists("exif_imagetype")) {
			return self::determine_extension_simple($filename);
		}
		
		$t = exif_imagetype($filename);
		if (!$t) {
			return false;
		}
		$t2ext = array(
			IMAGETYPE_GIF => "gif",
			IMAGETYPE_JPEG => "jpg",
			IMAGETYPE_PNG => "png",
			IMAGETYPE_SWF => "swf"
		);
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
		if (!$this->file_exists() || !$this->data->matches_file($this->path())) {
			$path = $this->path();
			$dir = dirname($path);
			Directory::depend($dir);
			$result = $this->data->copy_file($path);
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
		}
		return true;
	}
	
	/**
	 * Update Width and Height
	 *
	 * @return boolean
	 */
	private function _update_sizes() {
		if (!$this->member_is_empty("width") && !$this->member_is_empty("height")) {
			return true;
		}
		$this->_force_to_disk();
		$path = $this->path();
		if (empty($path)) {
			return false;
		}
		$result = @getimagesize($path);
		if (!is_array($result)) {
			trigger_error("Content_Image::_update_sizes(): getimagesize failed on \"$path\"" . _backtrace(), E_USER_WARNING);
			return false;
		}
		$this->set_member("width", $result[0]);
		$this->set_member("height", $result[1]);
		return true;
	}
	
	/**
	 * Rotate image $degrees degrees in one direction or the other
	 * 
	 * @param integer $degrees
	 * @return boolean
	 */
	public function rotate($degrees) {
		$this->_force_to_disk();
		$path = $this->path();
		$result = Image_Library::singleton()->image_rotate($path, $path, $degrees);
		if (!$result) {
			return null;
		}
		$this->data = Content_Data::from_path($path, true, true);
		$this->_update_sizes();
		return $this->store();
	}
	
	/**
	 * Returns an array of (width, height) with the new image constrained the the box size. 
	 * 
	 * Image result width is guaranteed to be <= $eidth
	 * Image result height is guaranteed to be <= $height
	 *  
	 * @param integer $width
	 * @param integer $height
	 * @return array
	 */
	public function constrain_dimensions($width, $height) {
		return Image_Library::constrain_dimensions($this->width, $this->height, $width, $height);
	}
	
	/**
	 * Given an image file, fix the orientation based on the EXIF Orientation data
	 * 
	 * Returns true if file was fixed or unchanged, "unsupported" if operation is not supported, and "failed" if conversion failed
	 *  
	 * @param string $file
	 * @return NULL|string|boolean
	 */
	public static function correct_orientation($file) {
		if (!function_exists('exif_read_data')) {
			return null;
		}
		$exif = @exif_read_data($file);
		if (!is_array($exif)) {
			return "unsupported";
		}
		$orientation = avalue($exif, 'Orientation', 'none');
		$rotate = null;
		switch ($orientation) {
			case "none":
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
		$result = Image_Library::singleton()->image_rotate($file, $file, $rotate);
		if (!$result) {
			return "failed";
		}
		return true;
	}
}

