<?php
declare(strict_types=1);
/**
 *
 *
 */

namespace zesk\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use zesk\Exception\ParseException;
use zesk\Timestamp as DateTimestamp;

class Timestamp extends Type
{
	public const TYPE = 'timestamp'; // modify to match your type name

	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
	{
		return $platform->getDateTimeTzTypeDeclarationSQL($column);
	}

	public function convertToPHPValue($value, AbstractPlatform $platform): DateTimestamp
	{
		$stamp = DateTimestamp::nowUTC();

		try {
			return $stamp->parse($value);
		} catch (ParseException) {
			return $stamp->setEmpty();
		}
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
	{
		// This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
		/* @var $value DateTimestamp */
		$dateTime = $value->datetime();
		if ($dateTime === null || $value->isEmpty()) {
			return null;
		}
		return $dateTime->format($platform->getDateTimeFormatString());
	}

	public function getName(): string
	{
		return self::TYPE; // modify to match your constant name
	}
}
