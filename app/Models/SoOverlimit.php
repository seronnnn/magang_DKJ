<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoOverlimit extends Model
{
    protected $table = 'so_overlimit';

    protected $fillable = [
        'invoice_id', 'period_id',
        'so_without_od', 'so_with_od', 'total_so',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function period()
    {
        return $this->belongsTo(ArPeriod::class, 'period_id');
    }
}