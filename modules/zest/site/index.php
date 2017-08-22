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
	$application = require_once dirname(dirname(__FILE__)) . '/zest.application.php';
	/* @var $application Zest */
	$application->index();
} catch (Exception $e) {
	echo zesk()->application()->theme(zesk()->classes->hierarchy($e), array(
		"exception" => $e
	), array(
		"first" => true
	));
}
