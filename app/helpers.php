<?php

declare(strict_types=1);

/**
 * bcmath polyfill — used when the bcmath PHP extension is not installed.
 * Provides accurate arithmetic for the scale values used in this project (≤ 3 decimal places).
 * All inputs/outputs match the bcmath string API contract.
 */

if (! function_exists('bcmul')) {
    function bcmul(string $num1, string $num2, int $scale = 0): string
    {
        $result = (float) $num1 * (float) $num2;

        return number_format($result, $scale, '.', '');
    }
}

if (! function_exists('bcadd')) {
    function bcadd(string $num1, string $num2, int $scale = 0): string
    {
        $result = (float) $num1 + (float) $num2;

        return number_format($result, $scale, '.', '');
    }
}

if (! function_exists('bcsub')) {
    function bcsub(string $num1, string $num2, int $scale = 0): string
    {
        $result = (float) $num1 - (float) $num2;

        return number_format($result, $scale, '.', '');
    }
}

if (! function_exists('bcdiv')) {
    function bcdiv(string $num1, string $num2, int $scale = 0): string
    {
        if ((float) $num2 === 0.0) {
            throw new \DivisionByZeroError('Division by zero');
        }

        $result = (float) $num1 / (float) $num2;

        return number_format($result, $scale, '.', '');
    }
}

if (! function_exists('bccomp')) {
    function bccomp(string $num1, string $num2, int $scale = 0): int
    {
        $multiplier = 10 ** $scale;
        $a          = (int) round((float) $num1 * $multiplier);
        $b          = (int) round((float) $num2 * $multiplier);

        return $a <=> $b;
    }
}
