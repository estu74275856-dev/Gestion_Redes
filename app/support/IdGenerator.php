<?php

namespace App\support;

use InvalidArgumentException;
use RuntimeException;

final class IdGenerator
{
    private const ALPHABET = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function generate(string $prefix, int $length): string
    {
        if (strlen($prefix) !== 1 || $length < 2) {
            throw new InvalidArgumentException('El prefijo debe tener un carácter y el ID al menos dos.');
        }

        $id = strtoupper($prefix);
        $alphabetLength = strlen(self::ALPHABET);
        for ($position = 1; $position < $length; $position++) {
            $id .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $id;
    }

    public static function generateUnique(string $prefix, int $length, callable $exists): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $id = self::generate($prefix, $length);
            if (!$exists($id)) {
                return $id;
            }
        }

        throw new RuntimeException('No se pudo generar un ID único después de varios intentos.');
    }
}
