<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';
    public $timestamps = false;

    protected $fillable = [
        'sales_id', 'trade_id', 'customer_id',
        'due_date', 'baseline_date', 'tax_date',
        'currency_type', 'amount_paid',
    ];

    protected $casts = [
        'due_date'      => 'date',
        'baseline_date' => 'date',
        'tax_date'      => 'date',
        'amount_paid'   => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function arRecords()
    {
        return $this->hasMany(ArRecord::class, 'invoice_id');
    }

    public function soOverlimits()
    {
        return $this->hasMany(SoOverlimit::class, 'invoice_id');
    }
}