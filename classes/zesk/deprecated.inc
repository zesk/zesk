<?php

/**
 * Declare this class to receive deprecated functions between releases.
 *
 * e.g.
 *
 * Zesk_Deprecated::load();
 *
 * @author kent
 */
abstract class Zesk_Deprecated {
	public static function load() {
	}
}

/**
 * _g Shortcut for zesk::get
 *
 * @param string $k
 *        	A global value to get
 * @param mixed $default
 *        	Value to return if value is not set globally
 * @return mixed The requested global
 * @deprecated 2014
 */
function _g($k, $default = null) {
	return zesk::get($k, $default);
}

/**
 * _g1 Shortcut for zesk::get1
 *
 * @param string $k
 *        	A global value or values to search
 * @param mixed $default
 *        	Value to return if value is not set globally
 * @return mixed The first one found
 * @deprecated 2014
 */
function _g1($k, $default = null) {
	return zesk::get1($k, $default);
}

/**
 * _gb Shortcut for zesk::getb
 *
 * @param string $k
 *        	A global value to get
 * @param mixed $default
 *        	Value to return if value is not set globally
 * @return mixed The requested global
 * @deprecated 2014
 */
function _gb($k, $default = null) {
	return zesk::getb($k, $default);
}

