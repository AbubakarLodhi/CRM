<?php

namespace App\Models;

use App\Enums\AttachmentMetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Category extends Model
{
    use HasUuids;

    /** @var string[] $fillable */
    protected $fillable = ['merchant_id','parent_id','name'];

    /** @var bool $incrementing */
    public $incrementing = false;

    /** @var string $keyType */
    protected $keyType = 'string';

    /**
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class,'parent_id');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class,'parent_id');
    }

    /**
     * @return HasMany
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * @return MorphOne
     */
    public function icon(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('meta_type', AttachmentMetaType::CATEGORY_ICON);

    }
}
