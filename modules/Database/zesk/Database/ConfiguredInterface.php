<?php
declare(strict_types=1);

namespace zesk\Database;

interface ConfiguredInterface
{
	public function hook_database_configure(): void;
}
