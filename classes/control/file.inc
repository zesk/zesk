<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/file.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:33:36 EDT 2008
 */
class Control_File extends Control {
	protected $theme = "control/file";

	/**
	 * File loaded from request
	 *
	 * @var array
	 */
	private $file = null;
	function __construct($options = false) {
		parent::__construct($options);
		$this->set_option("FileUpload", true); // Make a protected member TODO
		$this->upload(true);
	}
	public function file_name_column() {
		return $this->option("filecolumn", $this->column() . "_FileName");
	}
	function defaults() {
		parent::defaults();
		$this->object->set($this->file_name_column(), $this->option('filecolumn_default', ''));
	}

	function load() {
		$col = $this->column();
		$name = $this->name();
		$filecolumn = $this->file_name_column();
		if ($this->request->has($name)) {
			$this->object->set($col, $this->request->get($name));
		}
		if ($this->request->has($filecolumn)) {
			$this->object->set($filecolumn, $this->request->get($filecolumn));
		}
	}
	function validate() {
		$col = $this->column();
		$name = $this->name();
		$filecolumn = $this->file_name_column();

		$this->object->set($col, $this->request->get($name));
		$this->object->set($filecolumn, $this->request->get($filecolumn));

		try {
			$file = $this->request->file($name . '_file');
		} catch (Exception_Upload $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
			$this->error($e->getMessage());
			return false;
		}
		if (is_array($file)) {
			$fname = basename(avalue($file, "name"));
			$this->object->set($col, $fname);
			$checksum_col = $this->first_option('checksum_column;ChecksumColumn');
			if ($checksum_col) {
				$this->object->set($checksum_col, md5_file($file['tmp_name']));
			}
		}
		return $this->validate_required();
	}
	function path($set = null) {
		$name = $this->name() . "_path";
		return $set ? $this->object->set($name, $set) : $this->object->get($name);
	}
	function filename($set = null) {
		$name = $this->file_name_column();
		return $set ? $this->object->set($name, $set) : $this->object->get($name);
	}
	private function _file() {
		if (is_array($this->file)) {
			return $this->file;
		}
		return $this->file = $this->request->file($this->name() . "_file");
	}
	function original_name() {
		return avalue($this->_file(), 'name', null);
	}
	function submit() {
		$col = $this->column();
		$name = $this->name();
		$filecolumn = $this->file_name_column();
		$file = $this->_file();
		if (is_array($file)) {
			$dest_path = $this->object->apply_map($this->option("dest_path"));
			$file_mode = $this->option_integer("file_mode", 0640);
			$dir_mode = $this->option_integer("dir_mode", 0750);
			$hash_file = $this->option_bool("hash_file", false);
			$path = $this->request->file_migrate($file, $dest_path, $hash_file, $file_mode, $dir_mode);
			$this->path($path);
			$this->object->set($filecolumn, basename($path));
		}
		return parent::submit();
	}
}
