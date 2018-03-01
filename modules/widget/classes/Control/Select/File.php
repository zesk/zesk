<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:22:54 EDT 2008
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Select_File extends Control_Select {
	/**
	 * 
	 * @param string $options
	 */
	function initialize() {
		parent::initialize();
		$this->set_option("novalue", "");
	}
	/**
	 * 
	 * @return mixed[]
	 */
	function hook_options() {
		$map = Directory::ls($this->option("path", "/data/files/"), $this->option("filter", '/.*\.[A-Za-z0-9]+/'));
		$opts = array();
		
		foreach ($map as $k) {
			$opts[$map[$k]] = $map[$k];
		}
		return $opts;
	}
}
