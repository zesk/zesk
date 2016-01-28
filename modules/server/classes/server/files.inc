<?php

/**
 * Abstraction of file system 
 * 
 * @author kent
 */
abstract class Server_Files {
	
	/**
	 * Platform
	 *
	 * @var Server_Platform
	 */
	protected $platform = null;
	
	/**
	 * Create a new Server_Files
	 *
	 * @param Server_Platform $platform        	
	 */
	function __construct(Server_Platform $platform) {
		$this->platform = $platform;
	}
	/**
	 * Is this a directory
	 *
	 * @param string $dir        	
	 * @return boolean
	 */
	abstract function is_dir($dir);
	
	/**
	 * Is this a directory?
	 *
	 * @param unknown $file        	
	 * @return boolean
	 */
	abstract function is_file($file);
	
	/**
	 * Create directory
	 *
	 * @param string $pathname        	
	 * @param integer $mode
	 *        	Mode of path
	 * @param boolean $recursive        	
	 */
	abstract function mkdir($pathname, $mode = null);
	
	/**
	 * Change mode of path
	 *
	 * @param string $path        	
	 * @param integer $mode        	
	 */
	abstract function chmod($path, $mode);
	
	/**
	 * Get file system stats
	 *
	 * @see file::stat
	 * @param string $path        	
	 * @param string $section
	 *        	Section to retrieve
	 */
	abstract function stat($path, $section = null);
	
	/**
	 * Put a file
	 *
	 * @param string $path        	
	 * @param string $contents        	
	 * @return boolean
	 */
	abstract function file_put_contents($path, $contents);
	
	/**
	 * Get a file's contents
	 *
	 * @param string $path        	
	 * @return string
	 */
	abstract function file_get_contents($path);
	
	/**
	 * Copy a file
	 *
	 * @param string $source        	
	 * @param string $destination        	
	 * @return boolean
	 */
	abstract function copy($source, $dest);
	
	/**
	 * Does a file exist?
	 *
	 * @param string $source        	
	 * @return boolean
	 */
	abstract function file_exists($source);
	/**
	 * Get md5 of a file
	 *
	 * @param string $source        	
	 * @return string
	 */
	abstract function md5_file($source);
}