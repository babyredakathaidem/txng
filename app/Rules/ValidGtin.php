<?php

namespace App\Rules;

use App\Services\GS1Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate GTIN (Global Trade Item Number) theo chuẩn GS1.
 * Hỗ trợ GTIN-8, GTIN-12, GTIN-13, GTIN-14.
 * Check digit phải đúng theo thuật toán GS1 Mod-10.
 */
class ValidGtin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleaned = preg_replace('/\D/', '', (string) $value);

        if (! in_array(strlen($cleaned), [8, 12, 13, 14])) {
            $fail("Trường :attribute phải là GTIN-8, GTIN-12, GTIN-13, hoặc GTIN-14.");
            return;
        }

        $service = app(GS1Service::class);

        if (! $service->validateGTIN($cleaned)) {
            $fail("Trường :attribute có check digit không hợp lệ (không phải GTIN chuẩn GS1).");
        }
    }
}
