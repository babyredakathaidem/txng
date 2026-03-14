<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class QrScanLog extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'qrcode_id','enterprise_id','batch_id','qr_type','token',
        'expected_place_name','expected_lat','expected_lng','expected_radius_m',
        'scanned_at','lat','lng','distance_m',
        'device_name','device_platform','ip','user_agent',
        'decision','reason',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
