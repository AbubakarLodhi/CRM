<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasUuids;
    use HasRoles;
    protected $fillable =
        [
            'name',
            'email',
            'password',
            'status'
        ];

    protected $hidden = ['password'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => filled($value) ? Hash::make($value) : null
        );
    }
}
