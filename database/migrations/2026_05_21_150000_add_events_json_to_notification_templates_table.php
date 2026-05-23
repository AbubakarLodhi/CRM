<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->json('events')->nullable()->after('merchant_id');
        });

        DB::table('notification_templates')
            ->select(['id', 'event'])
            ->orderBy('id')
            ->each(function (object $row): void {
                $event = $row->event ?? null;

                if (! filled($event)) {
                    return;
                }

                DB::table('notification_templates')
                    ->where('id', $row->id)
                    ->update(['events' => json_encode([$event])]);
            });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropIndex(['event']);
            $table->dropColumn('event');
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('event')->nullable()->after('merchant_id');
        });

        DB::table('notification_templates')
            ->select(['id', 'events'])
            ->orderBy('id')
            ->each(function (object $row): void {
                $decoded = json_decode($row->events ?? '[]', true);
                $first = is_array($decoded) ? ($decoded[0] ?? null) : null;

                DB::table('notification_templates')
                    ->where('id', $row->id)
                    ->update(['event' => $first]);
            });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn('events');
            $table->index('event');
        });
    }
};
