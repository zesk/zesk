<?php

// Game-time decision
if (function_exists("apc_fetch")) {
	
	/* @var $zesk \zesk\Kernel */
	/* @var $application \zesk\Application */
	
	$application->modules->register_paths();
	$zesk->classes->register('Cache_APC');
}