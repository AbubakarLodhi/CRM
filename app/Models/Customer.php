<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','name','phone','email','city','reference_id'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function reference()
    {
        return $this->belongsTo(Customer::class, 'reference_id');
    }

    public function referencedBy()
    {
        return $this->hasMany(Customer::class, 'reference_id');
    }

}
