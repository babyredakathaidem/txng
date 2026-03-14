<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Alert extends Model
{
    use BelongsToTenant;    
    public $timestamps = false;

    protected $fillable = [
        'enterprise_id','batch_id','qrcode_id','qr_type','token',
        'type','message','created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
