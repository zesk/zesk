<?php
/**
 * @desc
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/zest.application.inc $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */
define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');

/**
 * Load Zesk.
 * Why have a loader? Makes development easier. Also makes it simpler to upgrade Zesk and revert easily.
 * @author kent
 */
class _zesk_loader_ {
	private static function _find_root() {
		if (strpos(__FILE__, '/modules/zest/') !== 0) {
			return dirname(dirname(dirname(__FILE__)));
		}
		if (array_key_exists('ZESK_ROOT', $_SERVER)) {
			return $_SERVER['ZESK_ROOT'];
		}
		$ff = array(
			dirname(__FILE__) . '/.zesk_root'
		);
		if (array_key_exists('HOME', $_SERVER)) {
			$ff[] = $_SERVER['HOME'] . "/.zesk/root";
		}
		$ff[] = '/etc/zesk/root';
		foreach ($ff as $f) {
			if (is_file($f)) {
				$f = file($f);
				return trim(array_shift($f));
			}
		}
		if (is_file(ZESK_APPLICATION_ROOT . '../zesk/zesk.inc')) {
			return dirname(ZESK_APPLICATION_ROOT) . '/zesk/';
		}
		die("Can't find ZESK_ROOT");
	}
	
	/**
	 * Define ZESK_ROOT and clean it up
	 * Load zesk
	 */
	public static function init() {
		if (!defined('ZESK_ROOT')) {
			$root = self::_find_root();
			define('ZESK_ROOT', rtrim($root, "/") . "/");
		}
		$zesk = require_once ZESK_ROOT . 'zesk.inc';
		/* @var $zesk zesk\Kernel */
		$zesk->autoloader->path(ZESK_APPLICATION_ROOT . 'classes');
		$zesk->application_class = "Zest";
		
		return $zesk;
	}
}

/*
 * Load Zesk
 */
$zesk = _zesk_loader_::init();

return Application::instance()->configure();

