<?php

namespace App\Services\Cards;

use Illuminate\Support\Str;

class CardCodeGenerator
{
    public function generate(): string
    {
        $body = Str::upper(Str::random(12));

        return 'G360-'.$body.'-'.$this->checkDigit($body);
    }

    public function normalizePrinted(?string $code): ?string
    {
        $normalized = Str::upper(preg_replace('/[^A-Z0-9]/i', '', trim((string) $code)) ?? '');

        return $normalized === '' ? null : $normalized;
    }

    private function checkDigit(string $body): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;

        foreach (str_split($body) as $index => $character) {
            $sum += strpos($alphabet, $character) * ($index + 1);
        }

        return $alphabet[$sum % strlen($alphabet)];
    }
}
