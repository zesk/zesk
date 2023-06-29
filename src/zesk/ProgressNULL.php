<?php
declare(strict_types=1);

namespace zesk;

use zesk\Interface\ProgressStack;

/**
 *
 */
class ProgressNULL implements ProgressStack
{
	public function progressPush(string $name): void
	{
		// No-op
	}

	public function progress(string $status = null, float $percent = null): void
	{
		// No-op
	}

	public function progressPop(): void
	{
		// No-op
	}
}
