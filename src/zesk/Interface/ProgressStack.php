<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Interface;

use zesk\Exception\Semantics;

/**
 *
 * @author kent
 *
 */
interface ProgressStack {
	/**
	 * @param string $name
	 * @return void
	 */
	public function progressPush(string $name): void;

	/**
	 * @param string|null $status
	 * @param float|null $percent
	 * @return void
	 */
	public function progress(string $status = null, float $percent = null): void;

	/**
	 * @return void
	 * @throws Semantics - If nothing to pop
	 */
	public function progressPop(): void;
}
