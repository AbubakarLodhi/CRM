<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OutboundMessage extends Model
{
    use HasUuids;

    protected $fillable = ['merchant_id','channel','recipient','subject','body','payload','status','provider','provider_message_id','error_message'];
    protected $casts = ['payload'=>'array'];
    public $incrementing = false;
    protected $keyType = 'string';
}
