<?php
class Class_Zesk_Content_Video extends Class_Object {

	public $columns = array(
		"ID", 
		"Name", 
		"Parent", 
		"Hash", 
		"Width", 
		"Height", 
		"URL", 
		"FilePath", 
		"Description", 
		"Created", 
		"Modified"
	);

	public $find_keys = array(
		"Name"
	);
}
