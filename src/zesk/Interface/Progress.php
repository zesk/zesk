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
interface Interface_Progress {
	public function progressPush(string $name): void;

	public function progress(string $status = null, float $percent = null): void;

	public function progressPop(): void;
}
