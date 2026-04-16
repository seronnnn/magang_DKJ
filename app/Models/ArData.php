<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ArData extends Model
{
    protected $table = 'ar_data';

    protected $fillable = [
        'plant', 'customer_id', 'customer_name', 'collection_by',
        'current', 'days_1_30', 'days_30_60', 'days_60_90', 'days_over_90',
        'total', 'so_without_od', 'so_with_od', 'total_so',
        'ar_target', 'ar_actual', 'period',
    ];

    protected $casts = [
        'current'      => 'integer',
        'days_1_30'    => 'integer',
        'days_30_60'   => 'integer',
        'days_60_90'   => 'integer',
        'days_over_90' => 'integer',
        'total'        => 'integer',
        'ar_target'    => 'integer',
        'ar_actual'    => 'integer',
        'period'       => 'date',
    ];

    /* ──────────── Scopes ──────────── */

    public function scopeByPlant(Builder $q, ?string $plant): Builder
    {
        return $plant ? $q->where('plant', $plant) : $q;
    }

    public function scopeByCollector(Builder $q, ?string $collector): Builder
    {
        return $collector ? $q->where('collection_by', $collector) : $q;
    }

    public function scopeByPeriod(Builder $q, string $period = '2026-01-31'): Builder
    {
        return $q->where('period', $period);
    }

    /* ──────────── Accessors ──────────── */

    public function getCollectionRateAttribute(): ?float
    {
        if (!$this->ar_target) return null;
        return round($this->ar_actual / $this->ar_target * 100, 1);
    }

    public function getCollectionStatusAttribute(): string
    {
        $rate = $this->collection_rate;
        if ($rate === null) return 'no-target';
        if ($rate >= 100)   return 'achieved';
        if ($rate >= 70)    return 'partial';
        return 'none';
    }

    public function getOverdueAttribute(): int
    {
        return $this->days_60_90 + $this->days_over_90;
    }

    public function getIsOverlimitAttribute(): bool
    {
        return $this->so_with_od > 0;
    }
}
