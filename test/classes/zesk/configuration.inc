<?php
	
namespace zesk;

class Test_Zesk_Configuration extends Test_Unit {
	
	public function test_basics() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->configuration->TEST->ROOT = "Hello";
		
		dump($zesk->configuration->TEST->ROOT->value());
	}
}
