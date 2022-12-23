<?php declare(strict_types=1);
/**
 *
 */
require_once __DIR__ . '/vendor/autoload.php';

return zesk\Kernel::singleton()->applicationClass('zesk\\Application_Server')->create_application()->configure();
