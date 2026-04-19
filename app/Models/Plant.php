<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $table = 'plants';
    public $timestamps = false;

    protected $fillable = ['customer_id', 'code', 'name'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}