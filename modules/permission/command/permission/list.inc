<?php

/**
 * List all permissions available in the system
 * 
 * @author kent
 * @category Permission
 */
class Command_Permission_List extends Command_Base {
	function run() {
		$perms = Module_Permission::permissions();
		dump($perms);
		return 0;
	}
}