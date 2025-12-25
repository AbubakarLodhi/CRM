<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Branch extends Model
{
    use HasUuids;
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = ['merchant_id','business_id','name','address','phone','status'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_VERIFIED,
            self::STATUS_REJECTED,
        ];
    }
}
