<?php

namespace App\Support;

class CsvFormatter
{
    public static function cell(mixed $value): string
    {
        $value = $value === null ? '' : (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}