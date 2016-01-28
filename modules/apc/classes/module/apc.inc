<?php

class Module_APC extends Module {
	function initialize() {
		Cache::register_interface("apc", "Cache_APC");
	}
}
