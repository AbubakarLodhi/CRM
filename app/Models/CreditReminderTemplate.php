<?php

namespace App\Models;

use App\Enums\CreditReminderScheduleType;
use App\Enums\ReminderPeriodType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class CreditReminderTemplate extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'merchant_id',
        'name',
        'notification_template_id',
        'schedule_type',
        'offset_type',
        'offset_value',
        'repeat_type',
        'repeat_value',
        'is_enabled',
    ];

    protected $casts = [
        'offset_value' => 'integer',
        'repeat_value' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function creditReminders(): HasMany
    {
        return $this->hasMany(CreditReminder::class);
    }

    public function scheduleType(): ?CreditReminderScheduleType
    {
        return $this->schedule_type
            ? CreditReminderScheduleType::tryFrom($this->schedule_type)
            : null;
    }

    public function offsetPeriod(): ?ReminderPeriodType
    {
        return $this->offset_type
            ? ReminderPeriodType::tryFrom($this->offset_type)
            : null;
    }

    public function repeatPeriod(): ?ReminderPeriodType
    {
        return $this->repeat_type
            ? ReminderPeriodType::tryFrom($this->repeat_type)
            : null;
    }

    public function isRecurringSchedule(): bool
    {
        return $this->schedule_type === CreditReminderScheduleType::Recurring->value;
    }
}
