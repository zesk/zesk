<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class DebugTest extends UnitTest {
	public function test_calling_file(): void {
		$this->assertEquals(__FILE__, Debug::callingFile());
	}

	public function test_calling_function(): void {
		$this->assertStringContainsString(__FILE__, Kernel::callingFunction(0));
		$this->assertStringContainsString(__CLASS__, Kernel::callingFunction(0));
		$this->assertStringContainsString(__FUNCTION__, Kernel::callingFunction(0));
	}

	public function test_dump(): void {
		$this->assertEquals('(null)', Debug::dump(null));
		$this->assertEquals('(boolean) true', Debug::dump(true));
		$this->assertEquals('(boolean) false', Debug::dump(false));
		$this->assertEquals('array()', Debug::dump([]));
	}

	public function test_output(): void {
		ob_start();
		Debug::output($random = $this->randomHex());
		$content = ob_get_clean();
		$this->assertStringContainsString($random, $content);
	}
}
