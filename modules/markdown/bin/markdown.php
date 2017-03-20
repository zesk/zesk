#!/usr/bin/env php
<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/zesk.inc';

app()->modules->load("markdown");

cdn::add('/share/', '/share/', ZESK_ROOT . 'share/');

zesk()->objects->factory("Command_Markdown")->go();
