<?php
declare(strict_types=1);
/**
 *
 *
 */

namespace zesk\Doctrine\Types;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use zesk\IPv4;


class IPv4String extends Type {
	public const TYPE = 'ipString'; // modify to match your type name

	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
		$column['options'] = ['unsigned' => true] + ($column['options'] ?? []);
		return $platform->getIntegerTypeDeclarationSQL($column);
	}

	public function convertToPHPValue($value, AbstractPlatform $platform): string {
		return $value === null ? "0.0.0.0" : IPv4::fromInteger($value);
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): int {
		if (!Ipv4::valid($value)) {
			throw new ConversionException("Can not convert $value to an integer IP address");
		}
		return IPv4::toInteger($value);

	}

	public function getName(): string {
		return self::TYPE; // modify to match your constant name
	}
}
