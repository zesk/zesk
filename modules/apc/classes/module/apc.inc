<?php
/**
 * 
 */
use zesk\Cache;
/**
 * 
 * @author kent
 *
 */
class Module_APC extends zesk\Module {
	function initialize() {
		Cache::register_interface("apc", "Cache_APC");
	}
}
