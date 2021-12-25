<?php declare(strict_types=1);
namespace zesk;

class Progress_NULL implements Interface_Progress {
	public function progress($status = null, $percent = null): void {
		// No-op
	}

	public function progress_push($name): void {
		// No-op
	}

	public function progress_pop(): void {
		// No-op
	}
}
