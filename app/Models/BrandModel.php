<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BrandModel extends Model
{
    use HasUuids;


    protected $fillable = ['merchant_id', 'brand_id', 'name'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'brand_model_id');
    }

    public function addons()
    {
        return $this->hasMany(AddOn::class, 'brand_model_id');
    }
}
