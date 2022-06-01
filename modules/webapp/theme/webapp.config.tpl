<?php declare(strict_types=1);
namespace zesk;

$kernel = Kernel::factory();
$kernel->paths->setApplication('{root}');
$application = $kernel->create_application();
$application->configureInclude(json_decode(file_get_contents(__DIR__ . '/configuration.json')));
$application->modules->load('WebApp');

return $application->configure();
