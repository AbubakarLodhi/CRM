<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract, Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids, HasRoles,Notifiable,CanResetPassword;
    /** @var string */
    protected $keyType = 'string';

    /** @var string */
    const STATUS_PENDING = 'pending';

    /** @var string */
    const STATUS_VERIFIED = 'verified';

    /** @var string */
    const STATUS_REJECTED = 'rejected';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $guard_name = 'merchant';

    /** @var string[] */
    protected $fillable = ['name', 'email', 'merchant_id','email_verified_at', 'password', 'status', 'is_active'];

    /** @var string[] */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return string[]
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_VERIFIED,
            self::STATUS_REJECTED,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_users',
        )
            ->using(\App\Models\BranchUser::class)
            ->withTimestamps();
    }


    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Business::class,
            'business_users'
        )
            ->using(\App\Models\BusinessUser::class) // 👈 REQUIRED
            ->withTimestamps();
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function profilePhoto()
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', \App\Enums\AttachmentMetaType::PROFILE_PHOTO);
    }

}
