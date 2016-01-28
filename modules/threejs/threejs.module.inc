<?php

Module::register_paths();

if (is_dir($dir = dirname(__FILE__) . '/lib/build')) {
	zesk::share_path($dir, 'threejs');
	zesk::share_path(dirname(__FILE__) . '/lib/examples/js/libs', 'threejs-libs');
}

zesk::register_class('Module_threejs');
