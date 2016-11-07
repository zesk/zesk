#!/usr/bin/env php
<?php

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/zesk.inc';

Module::load('markdown');
cdn::add('/share/', '/share/', ZESK_ROOT . 'share/');

zesk::factory("Command_Markdown")->go();
