#!/usr/bin/env php
<?php

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/zesk.inc';

Module::load('php-mo');
cdn::add('/share/', '/share/', ZESK_ROOT . 'share/');
log::file(STDOUT);
log::level(log::DEBUG);

zesk::factory("Command_PHP_MO")->go();
