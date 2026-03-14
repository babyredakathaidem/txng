<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enterprise extends Model
{
    protected $fillable = [
        'name',
        'business_code','business_code_issued_at',
        'business_cert_no','business_cert_issued_place',
        'business_license_no','business_license_issued_place',

        'province','district','address_detail',

        'phone','email',
        'representative_name','representative_id',

        'business_cert_file_path',
        'status',

        'created_by',
        'admin_user_id',

        'approved_at','approved_by',
        'rejected_at','rejected_by','rejection_reason',
        'terms_accepted_at',
        'code',
        'gln',        // Global Location Number (TCVN 13274:2020)
        'tax_id',     // Mã số thuế bắt buộc
    ];

    public function validateGln(): bool
    {
        return \App\Services\GS1Validator::isValid($this->gln);
    }

    protected $appends = ['full_address'];

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_detail,
            $this->district,
            $this->province,
        ], fn ($v) => filled($v));

        return implode(', ', $parts);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}