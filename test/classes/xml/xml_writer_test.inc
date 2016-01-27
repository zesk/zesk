<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/xml/xml_writer_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class XML_Writer_Test extends Test_Unit {
	function test_main() {
		$x = new XML_Writer();
		
		$tag = "address";
		$attributes = false;
		$content = '';
		$x->push($tag, $attributes, $content);
		
		$x->top();
		
		$attributes = array('id' => 'west-3rd');
		$content = "1600 Pennsylvania Ave.";
		$x->add('street', $attributes, $content);
		
		$content = 'Some content';
		$x->addContent($content);
		
		$tag = "address";
		$x->pop($tag);
		
		$path = "foo";
		$create = true;
		$x->find($path, $create);
		//
		//$path = "bar";
		//$x->findAll($path);
		

		$options = false;
		$x->output($options);
		
		echo basename(__FILE__) . ": success\n";
	}
}