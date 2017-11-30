<?php
/**
 * 
 * @author kent
 *
 */
interface Interface_File_Cache {
	
	/**
	 * 
	 * @param unknown $url
	 * @param array $options
	 */
	public function _url($url, array $options = array());
	
	/**
	 * 
	 * @param unknown $url
	 */
	public function _info($url);
	
	/**
	 * @return Iterator
	 */
	public function _iterator();
}

