<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArPeriod extends Model
{
    protected $table = 'ar_periods';
    public $timestamps = false;

    protected $fillable = ['period_label', 'period_month'];

    protected $casts = [
        'period_month' => 'date',
        'created_at'   => 'datetime',
    ];

    public function arRecords()
    {
        return $this->hasMany(ArRecord::class, 'period_id');
    }

    public function soOverlimits()
    {
        return $this->hasMany(SoOverlimit::class, 'period_id');
    }
}