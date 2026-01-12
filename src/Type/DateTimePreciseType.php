<?php

namespace PfaffKIT\Essa\Adapters\Storage\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class DateTimePreciseType extends VarDateTimeImmutableType
{
    public const NAME = 'datetime_precise';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value->format('Y-m-d H:i:s.uP');
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        if (null === $value || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.uP', $value);

        if (!$dateTime) {
            $dateTime = new \DateTimeImmutable($value);
        }

        return $dateTime->setTimezone(new \DateTimeZone('UTC'));
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQLPlatform) {
            return 'TIMESTAMP(6) WITH TIME ZONE';
        }

        return parent::getSQLDeclaration($column, $platform);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
