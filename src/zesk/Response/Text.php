<?php
declare(strict_types=1);


namespace zesk\Response;

/**
 * The simplest of types
 *
 * @author kent
 *
 */
class Text extends Type
{
	public function output($content): void
	{
		echo $content;
	}

	public function toJSON(): array
	{
		return [];
	}
}
