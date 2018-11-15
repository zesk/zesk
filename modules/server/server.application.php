<?php
/**
 *
 */
require_once __DIR__ . '/vendor/autoload.php';

return zesk\Kernel::instance()->application_class("zesk\\Application_Server")->create_application()->configure();
