<?php declare(strict_types=1);
namespace zesk;

$kernel = Kernel::factory();
$kernel->paths->set_application('{root}');
$application = $kernel->create_application();
$application->configure_include(json_decode(file_get_contents(__DIR__ . '/configuration.json')));
$application->modules->load('WebApp');

return $application->configure();
