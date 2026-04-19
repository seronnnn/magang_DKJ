<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'collector_id', 'customer_id', 'customer_name',
        'pic_name', 'email', 'address', 'remark',
        'whatsapp_number', 'office_number',
    ];

    public function collector()
    {
        return $this->belongsTo(Collector::class, 'collector_id');
    }

    public function plants()
    {
        return $this->hasMany(Plant::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /** Convenience: first plant code */
    public function getPlantCodeAttribute(): ?string
    {
        return $this->plants->first()?->code;
    }

    /** Convenience: collector name */
    public function getCollectorNameAttribute(): ?string
    {
        return $this->collector?->name;
    }
}