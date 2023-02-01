<?php
declare(strict_types=1);

namespace zesk\ORM;

/**
 * Hook is called after the schema has been updated
 */

interface Interface_Schema_Updated {
	public function hook_schema_updated(): void;
}
