<?php
namespace App\Models;

use App\Enums\AttachmentType;
use App\Enums\AttachmentMetaType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Contracts\Auditable;

class Attachment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids, SoftDeletes;

    /** @var string[] $fillable */
    protected $fillable = [
        'merchant_id',
        'type',
        'meta_type',
        'photo_url',
    ];

    /** @var string[] $casts Attribute type casting */
    protected $casts = [
        'type' => AttachmentType::class,
        'meta_type' => AttachmentMetaType::class,
    ];

    /**
     * @return MorphTo
     */
    public function attachable():morphTo
    {
        return $this->morphTo();
    }
}

