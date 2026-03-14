<?php

namespace App\Services;

class GS1Validator
{
    /**
     * Tính toán hoặc kiểm tra Check Digit cho mã GS1 (GTIN-14, SSCC-18, GLN-13, v.v.)
     * Theo thuật toán TCVN 13274:2020 / GS1 Standard
     */
    public static function isValid(string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (empty($code)) return false;

        $length = strlen($code);
        $data = substr($code, 0, -1);
        $checkDigit = (int) substr($code, -1);

        return self::calculateCheckDigit($data) === $checkDigit;
    }

    /**
     * Thuật toán: 
     * 1. Nhân các số ở vị trí lẻ (từ phải sang trái) với 3, vị trí chẵn với 1.
     * 2. Tổng lại.
     * 3. Số kiểm tra là số cần thiết để tổng chia hết cho 10.
     */
    public static function calculateCheckDigit(string $data): int
    {
        $data = strrev(preg_replace('/\D/', '', $data));
        $sum = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $digit = (int) $data[$i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }

        $mod = $sum % 10;
        return ($mod === 0) ? 0 : 10 - $mod;
    }

    public static function formatGTIN14(string $gtin): string
    {
        $gtin = str_pad(preg_replace('/\D/', '', $gtin), 13, '0', STR_PAD_LEFT);
        $gtin = substr($gtin, 0, 13);
        return $gtin . self::calculateCheckDigit($gtin);
    }
}
