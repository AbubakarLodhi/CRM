<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Business extends Model
{
    use HasUuids;



    protected $fillable = ['merchant_id','name','description','status'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

}
