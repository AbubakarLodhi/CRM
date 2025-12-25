<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Customer extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','name','phone','email','address'];
    public $incrementing = false;
    protected $keyType = 'string';
}
