<?php

namespace App\Support;

use InvalidArgumentException;

final class PhoneNumber
{
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '' || preg_match('/[a-z]/i', $value) === 1) {
            throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        } elseif (str_starts_with($digits, '620')) {
            $digits = '62'.substr($digits, 3);
        }

        if (! str_starts_with($digits, '62')) {
            throw new InvalidArgumentException('Nomor WhatsApp harus memakai kode negara Indonesia atau diawali 08.');
        }

        $national = substr($digits, 2);

        if (strlen($national) < 8 || strlen($national) > 13 || ! str_starts_with($national, '8')) {
            throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        }

        return $digits;
    }
}
