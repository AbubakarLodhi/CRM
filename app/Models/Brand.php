<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Brand extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','category_id','name'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function models()
    {
        return $this->hasMany(BrandModel::class,'brand_id');
    }
}
