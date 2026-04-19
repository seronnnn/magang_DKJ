<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionLog extends Model
{
    protected $table = 'collection_logs';
    const UPDATED_AT = null;

    protected $fillable = [
        'ar_record_id', 'user_id', 'amount_collected', 'notes', 'collected_at',
    ];

    protected $casts = [
        'amount_collected' => 'decimal:2',
        'collected_at'     => 'datetime',
    ];

    public function arRecord()
    {
        return $this->belongsTo(ArRecord::class, 'ar_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}