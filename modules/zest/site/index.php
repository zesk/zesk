<?php
/**
 * @desc
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/site/index.php $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */

try {
	require_once dirname(dirname(__FILE__)) . '/zest.application.inc';
	Application::instance()->main();
} catch (Exception $e) {
	echo Application::instance()->theme(zesk::class_hierarchy($e), array(
		"exception" => $e
	), array(
		"first" => true
	));
}