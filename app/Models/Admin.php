<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Admin extends Authenticatable  implements CanResetPasswordContract
{
    use HasUuids, HasRoles,Notifiable,CanResetPassword;


    /** @var string[] $fillable */
    protected $fillable = ['name', 'email', 'password', 'status'];

    /** @var string[] $hidden */
    protected $hidden = ['password'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';


    /**
     * @return MorphOne
     */
    public function profilePhoto():MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', \App\Enums\AttachmentMetaType::PROFILE_PHOTO);
    }

}
