<?php

define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

require_once dirname(dirname(__FILE__)) . 'test.application.inc';

Application::instance()->main();
