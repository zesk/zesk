<?php
class Metafile_CFB_Test extends Test_Unit {

	function test_msformat() {
		$module = Module::load('msformat');

		$this->assert_equal(avalue($module, "loaded"), true);

		dump($module);

		$test_file = path($module['path'], 'test-data/conference.doc');

		$stream = new Stream_File($test_file);

		$cfb_file = new Metafile_CFB($stream);
		$cfb_file->validate();
		echo var_export($cfb_file);
	}
}
