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
	/* @var $application Zest */
	$application = require_once dirname(dirname(__FILE__)) . '/zest.application.inc';
	$application->index();
} catch (Exception $e) {
	global $zesk;
	echo app()->theme($zesk->classes->hierarchy($e), array(
		"exception" => $e
	), array(
		"first" => true
	));
}