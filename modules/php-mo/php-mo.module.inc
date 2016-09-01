<?php

Module::register_paths();

zesk::zesk_command_path(dirname(__FILE__) . '/classes/command');
zesk\Classes::register('Module_PHP_MO');

