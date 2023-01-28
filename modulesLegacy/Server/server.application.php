<?php
declare(strict_types=1);
/**
 *
 */
require_once __DIR__ . '/vendor/autoload.php';

use Server\classes\Application\Application_Server;
use zesk\Application as App;
use zesk\Kernel as AppFactory;

return AppFactory::createApplication([
	App::OPTION_APPLICATION_CLASS => Application_Server::class,
])->configure();
