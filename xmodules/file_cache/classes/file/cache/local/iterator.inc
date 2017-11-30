<?php
use zesk\Exception_Directory_NotFound;

//use \SplFileInfo;
class File_Cache_Local_Iterator implements Iterator {
	public function __construct($cache_dir) {
		if (!is_dir($cache_dir)) {
			throw new Exception_Directory_NotFound($cache_dir);
		}
		$this->cache_dir = $cache_dir;
		$dbdir = path($this->cache_dir, "db");
		$this->iterator = is_dir($dbdir) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator(path($this->cache_dir, "db"), FilesystemIterator::SKIP_DOTS)) : null;
	}
	
	/**
	 * Cache directory
	 * @var string
	 */
	private $cache_dir = null;
	/**
	 *
	 * @var unknown_type
	 */
	private $iterator = null;
	/**
	 * Current unserialized data structure
	 * @var array
	 */
	private $current = null;
	/**
	 *
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->iterator = null;
		$this->current = null;
	}
	
	/**
	 *
	 * @see Iterator::current()
	 */
	public function current() {
		if ($this->current === null) {
			$path = $this->iterator->key();
			$this->current = unserialize(file_get_contents($path));
		}
		return $this->current;
	}
	
	/**
	 *
	 * @see Iterator::key()
	 */
	public function key() {
		$this->current();
		return $this->current['url'];
	}
	
	/**
	 *
	 * @see Iterator::next()
	 */
	public function next() {
		$this->iterator->next();
	}
	
	/**
	 *
	 * @see Iterator::valid()
	 */
	public function valid() {
		if (!$this->iterator) {
			return false;
		}
		while ($this->iterator->valid()) {
			$current = $this->iterator->current();
			/* @var $current SplFileInfo */
			if ($current->isDir()) {
				$this->iterator->next();
				continue;
			} else if ($current->isFile()) {
				if (in_array($current->getFilename(), array(
					".",
					".."
				))) {
					$this->iterator->next();
					continue;
				}
				return true;
			}
		}
		return false;
	}
}
