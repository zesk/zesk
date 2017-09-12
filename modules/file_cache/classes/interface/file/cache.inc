<?php
use zesk\Application;
interface Interface_File_Cache {
	public function _url(Application $application, $url, array $options = array());
	public function _info(Application $application, $url);
	
	/**
	 * @return Iterator
	 */
	public function _iterator(Application $application);
}

