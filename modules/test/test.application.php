<?php
require __DIR__ . '/vendor/autoload.php';

$zesk = zesk\Kernel::singleton();

$zesk->autoloader->path(__DIR__ . '/classes');

return app()->configure();
