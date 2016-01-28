<?php

zesk::autoload_path(__DIR__ . '/classes');
zesk::theme_path(__DIR__ . '/theme'); // TODO
if (is_dir(__DIR__ . '/share')) {
	zesk::share_path(__DIR__ . '/share/build', 'bootstrap-datetimepicker');
}

