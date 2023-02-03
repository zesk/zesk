<?php declare(strict_types=1);
namespace zesk\Logger;

interface Processor {
	/**
	 * @param array $context
	 * @return array
	 */
	public function process(array $context);
}
