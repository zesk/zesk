<?php
declare(strict_types=1);

namespace zesk\ORM\Interface;

/**
 * Hook is called after the schema has been updated
 */

interface SchemaUpdatedInterface
{
	public function hook_schema_updated(): void;
}
