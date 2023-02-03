<?php
declare(strict_types=1);

namespace zesk;

interface Interface_Configured {
	public function hook_database_configure(): void;
}
