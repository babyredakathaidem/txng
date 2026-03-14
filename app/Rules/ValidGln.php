<?php

namespace App\Rules;

use App\Services\GS1Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate GLN (Global Location Number) 13 số theo chuẩn GS1.
 * Check digit phải đúng theo thuật toán GS1 Mod-10.
 */
class ValidGln implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleaned = preg_replace('/\D/', '', (string) $value);

        if (strlen($cleaned) !== 13) {
            $fail("Trường :attribute phải là GLN 13 chữ số.");
            return;
        }

        $service = app(GS1Service::class);

        if (! $service->validateGLN($cleaned)) {
            $fail("Trường :attribute có check digit không hợp lệ (không phải GLN chuẩn GS1).");
        }
    }
}
