<?php

/**
 * Class_File_Data
 *
 * @author kent
 */
class Class_File_Data extends Class_Object {
	public $columns = array(
		"ID",
		"Size",
		"Hash",
		"Path",
		"Servers",
		"Created"
	);
	public $find_keys = array(
		"Hash"
	);
}
