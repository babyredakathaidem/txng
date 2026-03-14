<?php

namespace App\Rules;

use App\Services\GS1Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate format của event_code nội bộ theo chuẩn hệ thống.
 *
 * Format bắt buộc: EVT-{ENT_CODE}-{CTE_CODE}-{YYYYMM}-{SEQ}
 * Ví dụ:           EVT-AGU-HARVEST-202603-001
 *
 * Quy tắc:
 *  - Bắt đầu bằng "EVT-"
 *  - ENT_CODE: 2-10 ký tự chữ hoa hoặc số
 *  - CTE_CODE: 2-10 ký tự chữ hoa hoặc số
 *  - YYYYMM: 6 chữ số (năm + tháng)
 *  - SEQ: 3 chữ số
 */
class ValidEventCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(GS1Service::class);

        if (! $service->validateEventCode((string) $value)) {
            $fail(
                "Trường :attribute không đúng định dạng mã sự kiện. " .
                "Yêu cầu: EVT-{2-10 ký tự}-{2-10 ký tự}-{YYYYMM}-{3 số}. " .
                "Ví dụ: EVT-AGU-HARVEST-202603-001"
            );
        }
    }
}
