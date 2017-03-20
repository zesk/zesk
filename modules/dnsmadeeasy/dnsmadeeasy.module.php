<?php

/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */

$application->modules->load('server');
$application->modules->register_paths();

$zesk->classes->register('Server_Feature_DNS_DNSMadeEasy');
