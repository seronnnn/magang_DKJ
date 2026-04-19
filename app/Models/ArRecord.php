<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArRecord extends Model
{
    protected $table = 'ar_records';

    protected $fillable = [
        'invoice_id', 'period_id',
        'amount_current', 'amount_1_30_days', 'amount_30_60_days',
        'amount_60_90_days', 'amount_over_90_days',
        'total_ar', 'ar_target', 'ar_actual',
    ];

    protected $casts = [
        'amount_current'      => 'decimal:2',
        'amount_1_30_days'    => 'decimal:2',
        'amount_30_60_days'   => 'decimal:2',
        'amount_60_90_days'   => 'decimal:2',
        'amount_over_90_days' => 'decimal:2',
        'total_ar'            => 'decimal:2',
        'ar_target'           => 'decimal:2',
        'ar_actual'           => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function period()
    {
        return $this->belongsTo(ArPeriod::class, 'period_id');
    }

    public function collectionLogs()
    {
        return $this->hasMany(CollectionLog::class, 'ar_record_id');
    }

    /* ── Accessors ── */

    public function getCollectionRateAttribute(): ?float
    {
        if (!$this->ar_target || $this->ar_target == 0) return null;
        return round($this->ar_actual / $this->ar_target * 100, 1);
    }

    public function getCollectionStatusAttribute(): string
    {
        $rate = $this->collection_rate;
        if ($rate === null)  return 'no-target';
        if ($rate >= 100)    return 'achieved';
        if ($rate >= 70)     return 'partial';
        return 'none';
    }

    public function getOverdueAttribute(): float
    {
        return $this->amount_60_90_days + $this->amount_over_90_days;
    }
}