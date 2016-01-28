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

require_once dirname(dirname(ZESK_APPLICATION_ROOT)) . '/zesk.inc';

zesk::application_class('Zest');

Module::load("zest");

Application::instance()->configure();

