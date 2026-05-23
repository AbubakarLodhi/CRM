<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->dropUnique(['sale_id']);
        });

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->foreignUuid('notification_template_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('notification_templates')
                ->nullOnDelete();

            $table->date('remind_at')->nullable()->after('notification_template_id');
        });

        $reminders = DB::table('credit_reminders')
            ->join('sales', 'sales.id', '=', 'credit_reminders.sale_id')
            ->select([
                'credit_reminders.id',
                'sales.due_date',
                'credit_reminders.reminder_type',
                'credit_reminders.first_reminder_value',
            ])
            ->get();

        foreach ($reminders as $row) {
            $dueDate = $row->due_date ? \Carbon\Carbon::parse($row->due_date)->startOfDay() : now()->startOfDay();
            $value = (int) ($row->first_reminder_value ?? 0);

            $remindAt = match ($row->reminder_type) {
                'months' => $dueDate->copy()->subMonths($value),
                'weeks' => $dueDate->copy()->subWeeks($value),
                default => $dueDate->copy()->subDays($value),
            };

            if ($remindAt->isPast()) {
                $remindAt = now()->startOfDay();
            }

            DB::table('credit_reminders')
                ->where('id', $row->id)
                ->update(['remind_at' => $remindAt->toDateString()]);
        }

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->dropColumn(['reminder_type', 'first_reminder_value']);
        });

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->index(['sale_id', 'is_active']);
            $table->index(['remind_at', 'next_send_at']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->string('reminder_type', 16)->default('days');
            $table->unsignedInteger('first_reminder_value')->default(0);
        });

        Schema::table('credit_reminders', function (Blueprint $table) {
            $table->dropForeign(['notification_template_id']);
            $table->dropColumn(['notification_template_id', 'remind_at']);
            $table->unique('sale_id');
        });
    }
};
