<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:33:36 EDT 2008
 */
namespace zesk;

class Control_File extends Control {
	/**
	 * File loaded from request
	 *
	 * @var array
	 */
	private $file = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->upload(true);
	}

	/**
	 *
	 * @return mixed|string
	 */
	public function file_name_column() {
		return $this->option("filecolumn", $this->column() . "_FileName");
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::defaults()
	 */
	public function defaults() {
		parent::defaults();
		$this->object->set($this->file_name_column(), $this->option('filecolumn_default', ''));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::load()
	 */
	public function load() {
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

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::validate()
	 */
	public function validate() {
		$col = $this->column();
		$name = $this->name();
		$filecolumn = $this->file_name_column();

		$this->object->set($col, $this->request->get($name));
		$this->object->set($filecolumn, $this->request->get($filecolumn));

		try {
			$file = $this->request->file($name . '_file');
		} catch (Exception_Upload $e) {
			$this->application->hooks->call("exception", $e);
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

	/**
	 *
	 * @param string $set
	 * @return \zesk\Model|mixed|NULL|mixed[]|NULL[]|mixed[][]|NULL[][]
	 */
	public function path($set = null) {
		$name = $this->name() . "_path";
		return $set ? $this->object->set($name, $set) : $this->object->get($name);
	}

	/**
	 *
	 * @param unknown $set
	 * @return \zesk\Model|mixed|NULL|mixed[]|NULL[]|mixed[][]|NULL[][]
	 */
	public function filename($set = null) {
		$name = $this->file_name_column();
		return $set ? $this->object->set($name, $set) : $this->object->get($name);
	}

	/**
	 *
	 * @return \zesk\NULL
	 */
	private function _file() {
		if (is_array($this->file)) {
			return $this->file;
		}
		return $this->file = $this->request->file($this->name() . "_file");
	}

	/**
	 *
	 * @return mixed|array
	 */
	public function original_name() {
		return avalue($this->_file(), 'name', null);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::submit()
	 */
	public function submit() {
		$col = $this->column();
		$name = $this->name();
		$filecolumn = $this->file_name_column();
		$file = $this->_file();
		if (is_array($file)) {
			$dest_path = $this->object->apply_map($this->option("dest_path"));
			$options = array();
			$options['file_mode'] = $this->option_integer("file_mode", 0640);
			$options['dir_mode'] = $this->option_integer("dir_mode", 0750);
			$options['hash'] = $this->option_bool("hash_file", false);
			$path = Request\File::instance($file)->migrate($this->application, $dest_path, $options);
			$this->path($path);
			$this->object->set($filecolumn, basename($path));
		}
		return parent::submit();
	}
}
