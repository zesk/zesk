<?php
declare(strict_types=1);

namespace zesk;

use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeTestHook;

final class PHPUnit_TestHooks implements BeforeFirstTestHook, AfterLastTestHook, BeforeTestHook {
	public function executeBeforeFirstTest(): void {
		// called before the first test is being run
		// print("Hello, world.\n");
	}

	public function executeBeforeTest(string $test): void {
		// print("Before test: $test");
	}

	public function executeAfterLastTest(): void {
		// called after the last test has been run
		// print("Goodbye, cruel world.\n");
	}
}
