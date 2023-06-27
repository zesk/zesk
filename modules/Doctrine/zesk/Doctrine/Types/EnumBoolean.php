<?php
declare(strict_types=1);
/**
 *
 *
 */

namespace zesk\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use zesk\Types;

class EnumBoolean extends Type
{
	public const TYPE = 'enumBoolean'; // modify to match your type name

	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
	{
		return 'ENUM(\'false\',\'true\')';
	}

	public function convertToPHPValue($value, AbstractPlatform $platform): ?bool
	{
		return $value === null ? null : Types::toBool($value);
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
	{
		return ($value === null) ? null : ($value ? 'true' : 'false');
	}

	public function getName(): string
	{
		return self::TYPE; // modify to match your constant name
	}
}
