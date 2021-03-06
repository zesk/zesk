<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @see Class_Content_Data
 * @author kent
 * @property integer $id
 * @property string $md5hash
 * @property string $type
 * @property array $data
 * @property integer $size
 * @property Timestamp $checked
 * @property Timestamp $missing
 */
class Content_Data extends ORM {
	/**
	 * Whether we checked the database for max allowed packet size
	 *
	 * @var unknown
	 */
	public static $checked_db = false;

	/**
	 * Retrieve user-configurable settings for this object
	 *
	 * @return multitype:multitype:string
	 */
	public static function settings() {
		return parent::settings() + array(
			'database_size_threshold' => array(
				'type' => 'filesize',
				'label' => 'database size Threshold',
				'description' => 'The size of file which should automatically be stored in the database.',
				'help_url' => 'https://api.zesk.com/' . __CLASS__ . '::database_size_threshold',
			),
		);
	}

	/**
	 * Given a string of data, create a Content_data object
	 *
	 * @param string $data
	 * @param boolean $register
	 *        	Check to see if the object already exists in database, and return database copy if
	 *        	so
	 * @return Content_data
	 */
	public static function from_string(Application $application, $data, $register = true) {
		return self::from_type($application, $data, null, null, null, $register);
	}

	/**
	 * Given a path, copy a file to create a Content_data object
	 *
	 * @param string $path
	 * @param boolean $register
	 *        	Check to see if the object already exists in database, and return database copy if
	 *        	so
	 * @return Content_data
	 */
	public static function copy_from_path(Application $application, $path, $register = true) {
		return self::from_path($application, $path, true, $register);
	}

	/**
	 * Given a path, moving a file to create a Content_data object
	 * Upon success, old file is removed.
	 *
	 * @param string $path
	 * @param boolean $register
	 *        	Check to see if the object already exists in database, and return database copy if
	 *        	so
	 * @return Content_data
	 */
	public static function move_from_path(Application $application, $path, $register = true) {
		return self::from_path($application, $path, false, $register);
	}

	/**
	 * Internal registration for Content_data
	 *
	 * @param string $path
	 * @param boolean $copy
	 *        	Copy file, don't move it
	 * @param boolean $register
	 *        	Check to see if the object already exists in database, and return database copy if
	 *        	so
	 * @return Content_data
	 */
	public static function from_path(Application $app, $path, $copy = true, $register = true) {
		if (!file_exists($path)) {
			throw new Exception_File_NotFound($path);
		}
		$md5 = md5_file($path);
		$size = filesize($path);
		$threshold = self::database_size_threshold($app);
		$type = $size <= $threshold ? 'data' : 'path';
		if ($register) {
			$fields = array();
			$fields['md5hash'] = $md5;
			$object = $app->orm_factory(__CLASS__, $fields);
			$row = $object->exists();
			if ($row) {
				$object->object_status(self::object_status_exists);
				return $object->initialize($row, true);
			}
		}
		if ($type === 'path') {
			$data = self::_copy_data($app, $path, $copy, $md5);
		} else {
			$data = file_get_contents($path);
		}
		return self::from_type($app, $data, $type, $size, $md5, $register);
	}

	/**
	 * Internal version of copy_from_path, move_from_path
	 *
	 * @param string $source_path
	 * @param boolean $copy
	 *        	Copy data, don't move
	 * @param string $md5
	 *        	MD5 of target file
	 * @param string $data
	 *        	If supplied, create file from this data
	 * @throws Exception_File_Create
	 * @throws Exception_Directory_Create
	 * @return multitype:string unknown Ambigous <string, mixed>
	 */
	private static function _copy_data(Application $app, $source_path, $copy, $md5, $data = null) {
		$result = array();
		$result['data_path'] = $app->paths->data();
		$result['original_path'] = $source_path;
		$result['path'] = 'content/data/' . $md5 . "." . File::extension($source_path);

		$dest = path($result['data_path'], $result['path']);

		Directory::depend(dirname($dest));

		$locale = $app->locale;

		if ($data !== null) {
			if (!file_put_contents($dest, $data)) {
				throw new Exception_File_Create($locale->__("Can not copy {size} data to {dest}", array(
					"size" => strlen($data),
					"dest" => $dest,
				)));
			}
		} elseif ($copy) {
			if (!copy($source_path, $dest)) {
				throw new Exception_File_Create($locale->__("Can not copy {path} to {dest}", array(
					"path" => $source_path,
					"dest" => $dest,
				)));
			}
		} else {
			if (!rename($source_path, $dest)) {
				throw new Exception_File_Create($locale->__("Can not rename {path} to {dest}", array(
					"path" => $source_path,
					"dest" => $dest,
				)));
			}
		}
		return $result;
	}

	/**
	 * Register object with a given data type
	 *
	 * @param mixed $data
	 *        	Binary data or serialized data
	 * @param string $type
	 *        	type of storage for data
	 * @param string $size
	 *        	integer
	 * @param string $hash
	 *        	Hash of data
	 * @param string $register
	 *        	Register this object (load or store)
	 * @return Ambigous <object, string, Object>
	 */
	private static function from_type(Application $application, $data, $type = 'data', $size = null, $hash = null, $register = true) {
		$fields = array();
		$fields['type'] = $type;
		$fields['data'] = $data;
		$fields['size'] = $size === null ? strlen($data) : $size;
		$fields['md5hash'] = $hash === null ? md5($data) : $hash;
		$object = $application->orm_factory(__CLASS__, $fields);
		return ($register) ? $object->register() : $object;
	}

	/**
	 * Extract the path from the data array (for ->type === 'data' ONLY)
	 *
	 * @return Ambigous <string, mixed>
	 */
	private function _filepath() {
		assert($this->type === 'data');
		$path = avalue($this->data, 'path');
		return $this->application->paths->data($path);
	}

	/**
	 * Retrieve the path of a file to copy this.
	 *
	 * Handles creating a temp file for database stored objects.
	 *
	 * @return string
	 */
	public function filepath() {
		if ($this->type === 'path') {
			return $this->_filepath();
		}
		// Copy data to a local, temp path
		if ($this->temp_path && !file_exists($this->temp_path)) {
			$this->temp_path = null;
		}
		if (!$this->temp_path) {
			$this->temp_path = File::temporary($this->temp_path);
			file_put_contents($this->temp_path, $this->data);
		}
		return $this->temp_path;
	}

	/**
	 * Return a file pointer to the data in this file
	 *
	 * @param mixed $mode
	 *        	fopen mode
	 * @see fopen
	 * @return resource
	 */
	public function fopen($mode) {
		return fopen($this->filepath(), $mode);
	}

	/**
	 * Copy file to a location
	 *
	 * @param string $destination
	 * @return boolean
	 */
	public function copy_file($destination) {
		if ($this->type === 'path') {
			if (!file_exists($this->_filepath())) {
				// Note that this can happen on load-balanced applications
				// where files are not synced between systems.
				throw new Exception_File_NotFound($this->_filepath());
			}
			return copy($this->_filepath(), $destination);
		} else {
			return file_put_contents($destination, $this->data()) !== false;
		}
	}

	/**
	 * Retrieve the file data as a PHP binary "string"
	 *
	 * @return string
	 */
	public function data() {
		if ($this->type === 'path') {
			return file_get_contents($this->_filepath());
		} else {
			return $this->data;
		}
	}

	/**
	 * Retrieve the data size as a double-precision integer
	 *
	 * @return integer
	 */
	public function size() {
		return $this->size;
	}

	/**
	 * Does the destination file match our database version?
	 *
	 * @param string $destination
	 *        	Path to destination file
	 * @return boolean
	 */
	public function matches_file($destination) {
		if ($this->size !== filesize($destination)) {
			return false;
		}
		$result = $this->md5hash === md5_file($destination);
		if ($result === true) {
			return true;
		}
		return false;
	}

	/**
	 * Internal validation routing - checks consistency of data in files
	 *
	 * @param string $computed_md5
	 * @param integer $computed_size
	 */
	private function check_md5_and_size($computed_md5, $computed_size) {
		if (strcasecmp($computed_md5, $this->md5hash) !== 0) {
			$this->application->logger->error("Content_data({ID}) {noun} appears to have changed {suffix}: {md5hash} (old) !== {md5hashNew} (new)", array(
				'md5hashNew' => $computed_md5,
			) + $this->members());
			if ($this->option_bool('repair;repair_checksums')) {
				$this->md5hash = $computed_md5;
			}
		}
		if ($computed_size !== $this->size) {
			$this->application->logger->error("Content_data({ID}) {noun} appears to have changed size {suffix}: {size} (old) !== {sizeNew} (new)", array(
				'sizeNew' => $computed_size,
			) + $this->members());
			if ($this->first_option('repair;repair_sizes')) {
				$this->size = $computed_size;
			}
		}
	}

	/**
	 * Internal validation function, attempts to repair files when filesystem changes, etc.
	 */
	protected function validate_and_repair() {
		if ($this->type === 'path') {
			if (!is_array($this->data)) {
				$this->application->logger->error("Content_data({ID}) has non array data", $this->members());
				return;
			}
			$this_path = $this->_filepath();
			if (!file_exists($this_path)) {
				$path = $this->data['path'];
				$data_path = $this->data['data_path'];
				$old_path = path($data_path, $path);
				if (file_exists($old_path)) {
					if (File::copy_uid_gid($old_path, $this_path)) {
						$this->application->logger->notice("Content_data({ID}) Moved {old_path} to {new_path}", array(
							'old_path' => $old_path,
							"new_path" => $this_path,
						) + $this->members());
						$this->data['data_path'] = $this->application->paths->data();
					} else {
						$this->application->logger->error("Content_data({ID}) Unable to move {old_path} to {new_path}", array(
							'old_path' => $old_path,
							"new_path" => $this_path,
						) + $this->members());
					}
				} else {
					$this->application->logger->error("Content_data({ID}) appears to have disappeared: {old_path}/{new_path}", array(
						'old_path' => $old_path,
						"new_path" => $this_path,
					) + $this->members());
					if ($this->member_is_empty('missing')) {
						$this->missing = "now";
					}
				}
			}
			if (file_exists($this_path)) {
				$this->check_md5_and_size(md5_file($this_path), filesize($this_path), array(
					'noun' => 'file',
					'suffix' => $this_path,
				));
			}
		} elseif ($this->type !== 'data') {
			$this->application->logger->error("Content_data({ID}) Invalid type {type}", $this->members());
		} else {
			$this->check_md5_and_size(md5($this->data), strlen($this->data), array(
				'noun' => 'database data',
				'suffix' => '',
			));
		}
		$this->checked = "now";
		$this->store();
	}

	/**
	 *
	 * @return Content_Data
	 */
	protected function switch_storage() {
		if ($this->type === 'path') {
			$data = $this->data();
			$this->type = 'data';
		} else {
			$this->type = 'path';
			$data = $this->data();
			$data = self::_copy_data($this->application, 'db.data', false, md5($data), $data);
		}
		$this->data = $data;
		return $this->store();
	}

	/**
	 *
	 * @return mixed|mixed[]|\Configuration
	 */
	public static function database_size_threshold(Application $application) {
		/* @var $data Content_Data */
		$data = $application->orm_registry(__CLASS__);
		$result = $data->option_integer('database_size_threshold', 8 * 1024 * 1024); // Handles most images, which is what we want.
		if (!self::$checked_db) {
			$size = $data->database()->feature(Database::FEATURE_MAX_BLOB_SIZE);
			if ($size < $result) {
				$data->set_option("database_size_threshold", $result);
				$application->configuration->path_set(array(
					__CLASS__,
					"database_size_threshold",
				), $result);
				$application->logger->warning("{class}::database_size_threshold Database size threshold {result} is beyond database setting of {size} - adjusting", array(
					"class" => __CLASS__,
					'size' => $size,
					'result' => $result,
				));
				$result = $size;
				self::$checked_db = true;
			}
		}
		return $result;
	}

	/**
	 * Run cron hourly to check files in file system to make sure they are still consistent.
	 */
	public static function cron_hourly(Application $application) {
		foreach ($application->class_query(__CLASS__)->where("*checked|<=", 'DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY)')->orm_iterator() as $object) {
			$object->validate_and_repair();
		}
		$threshold = self::database_size_threshold($application);
		foreach ($application->class_query(__CLASS__)
			->where("*size|<=", $threshold)
			->where('type', 'path')
			->orm_iterator() as $object) {
			$object->switch_storage();
		}
		foreach ($application->class_query(__CLASS__)
			->where("*size|>", $threshold)
			->where('type', 'data')
			->orm_iterator() as $object) {
			$object->switch_storage();
		}
	}
}
