<?php declare(strict_types=1);
namespace zesk\Logger;

interface Handler {
	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($message, array $context = []): void;
}
