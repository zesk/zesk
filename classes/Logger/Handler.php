<?php
namespace zesk\Logger;

interface Handler {
	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($message, array $context = array());
}
