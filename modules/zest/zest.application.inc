<?php
/**
 * @desc
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/zest.application.inc $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */
require_once __DIR__ . '/vendor/autoload.php';

$zesk = zesk\Kernel::singleton();

$zesk->autoloader->path(__DIR__ . '/classes');
$zesk->application_class("Zest");

return $zesk->create_application()->set_application_root(__DIR__)->configure();
