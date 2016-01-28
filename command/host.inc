<?php

/**
 * Display the hostname according to Zesk
 *
 * @category Debugging
 * @alias uname
 */
class Command_Host extends Command_Base {
	function run() {
		echo system::uname() . "\n";
		return 0;
	}
}