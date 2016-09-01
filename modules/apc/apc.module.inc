<?php

// Game-time decision
if (function_exists("apc_fetch")) {
	Module::register_paths();
	zesk\Classes::register('Cache_APC');
}

