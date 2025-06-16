<?php
declare(strict_types=1);
/**
 *
 *
 */

namespace zesk\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use zesk\Exception\ParseException;
use zesk\Timestamp as DateTimestamp;

class Timestamp extends Type
{
	public const TYPE = 'timestamp'; // modify to match your type name

	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
	{
		return $platform->getDateTimeTzTypeDeclarationSQL($column);
	}

	public function convertToPHPValue($value, AbstractPlatform $platform): DateTimestamp|null
	{
		if (empty($value)) {
			return null;
		}
		$stamp = DateTimestamp::nowUTC();

		try {
			return $stamp->parse($value);
		} catch (ParseException) {
			return null;
		}
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
	{
		// This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
		/* @var $value DateTimestamp|null */
		if ($value === null) {
			return null;
		}
		assert($value instanceof Timestamp);
		$dateTime = $value->datetime();
		if ($dateTime === null) {
			return null;
		}
		return $dateTime->format($platform->getDateTimeFormatString());
	}

	public function getName(): string
	{
		return self::TYPE; // modify to match your constant name
	}
}
