<?php
namespace zesk;

class Import_Log_Webserver_Test extends Test_Unit {
	protected $load_modules = array(
		"import_log"
	);
	function test_apache() {
		$filename = dirname(__FILE__) . '/test-data/apache_access.log';
		$options = array();
		$testx = new Import_Log_WebServer_Apache($filename, $options);
	}
	function test_iis2() {
		$filename = dirname(__FILE__) . '/test-data/ex080307.log.txt';
		
		$options = null;
		$testx = new Import_Log_WebServer_IIS($filename, $options);
		
		$testx->read_line();
		
		$testx->open();
		
		$testx->rewind();
		
		$testx->current();
		
		$testx->key();
		
		$testx->next();
		
		$testx->valid();
	}
	function test_iis() {
		$filename = dirname(__FILE__) . '/test-data/ex080307.log.txt';
		$options = null;
		$testx = new Import_Log_WebServer_IIS($filename, $options);
		
		$line = $testx->read_line();
		
		$testx->open();
		
		$testx->rewind();
		
		$testx->current();
		
		$testx->key();
		
		$testx->next();
		
		$testx->valid();
	}
}
