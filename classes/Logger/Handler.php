<?php declare(strict_types=1);
namespace zesk\Logger;

interface Handler {
	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log(string $message, array $context = []): void;
}
