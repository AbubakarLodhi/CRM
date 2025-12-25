<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Category extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','parent_id','name'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function parent()
    {
        return $this->belongsTo(Category::class,'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Category::class,'parent_id');
    }
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}
