<?php
interface Interface_File_Cache {

	public function _url($url, array $options = array());

	public function _info($url);
	
	/**
	 * @return Iterator
	 */
	public function _iterator();
}

