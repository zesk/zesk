<?php

// Game-time decision
if (function_exists("apc_fetch")) {
	Module::register_paths();
	zesk::register_class('Cache_APC');
}

