<?php
namespace zesk;

class Metafile_CFB_Test extends Test_Unit {
	protected $load_modules = array(
		"msformat"
	);
	function test_msformat() {
		$module_path = $this->application->modules->path("msformat");
		
		$test_file = path($module_path, 'test-data/conference.doc');
		
		$stream = new Stream_File($test_file);
		
		$cfb_file = new Metafile_CFB($stream);
		$cfb_file->validate();
		echo var_export($cfb_file);
	}
}
