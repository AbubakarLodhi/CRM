<?php

namespace App\Models;

use App\Enums\ReminderPeriodType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class CreditReminder extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sale_id',
        'credit_reminder_template_id',
        'notification_template_id',
        'remind_at',
        'repeat_type',
        'repeat_value',
        'is_active',
        'last_sent_at',
        'next_send_at',
    ];

    protected $casts = [
        'remind_at' => 'date',
        'repeat_value' => 'integer',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CreditReminderTemplate::class, 'credit_reminder_template_id');
    }

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function repeatPeriod(): ?ReminderPeriodType
    {
        return $this->repeat_type ? ReminderPeriodType::tryFrom($this->repeat_type) : null;
    }

    public function isRepeating(): bool
    {
        return filled($this->repeat_type) && (int) $this->repeat_value > 0;
    }
}
