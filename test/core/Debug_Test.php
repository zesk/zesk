<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Debug_Test extends UnitTest {
	public function test_calling_file(): void {
		Debug::calling_file();
	}

	public function test_calling_function(): void {
		$depth = 1;
		calling_function($depth);
	}

	public function test_dump(): void {
		Debug::dump();
	}

	public function test_output(): void {
		ob_start();
		Debug::output($random = $this->randomHex());
		$content = ob_get_clean();
		$this->assertStringContainsString($random, $content);
	}
}
