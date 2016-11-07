#!/usr/bin/env php
<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/zesk.inc';

app()->modules->load('php-mo');

zesk()->objects->factory("Command_PHP_MO")->go();
