<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_APC extends Module {
	function initialize() {
		if (!function_exists("apc_fetch")) {
			throw new Exception_Unsupported("APC is not installed");
		}
		$this->zesk->classes->register('zesk\\Cache_APC');
		Cache::register_interface("apc", "zesk\\Cache_APC");
	}
}
