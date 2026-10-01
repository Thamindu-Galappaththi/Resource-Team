<?php

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'public_id')) {
                $table->ulid('public_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('reservations', 'reference')) {
                $table->string('reference', 30)->nullable()->unique()->after('public_id');
            }
            if (! Schema::hasColumn('reservations', 'requester_id')) {
                $table->foreignId('requester_id')->nullable()->after('reference')->constrained('users')->restrictOnDelete();
            }
            if (! Schema::hasColumn('reservations', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('requester_id')->constrained('users')->restrictOnDelete();
            }
            if (! Schema::hasColumn('reservations', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('created_by_user_id')->constrained('locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('reservations', 'type')) {
                $table->string('type', 30)->default(ReservationType::STANDARD->value)->after('location_id');
            }
            if (! Schema::hasColumn('reservations', 'purpose')) {
                $table->string('purpose', 500)->nullable()->after('description');
            }
            if (! Schema::hasColumn('reservations', 'attendee_count')) {
                $table->unsignedInteger('attendee_count')->nullable()->after('purpose');
            }
            if (! Schema::hasColumn('reservations', 'status')) {
                $table->string('status', 30)->default(ReservationStatus::DRAFT->value)->after('end_time');
                $table->index(['requester_id', 'status']);
                $table->index(['location_id', 'status', 'reservation_date']);
            }
            if (! Schema::hasColumn('reservations', 'submitted_at')) {
                $table->dateTime('submitted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('reservations', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('reservations', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('reservations', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('reservations', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('cancellation_reason');
            }
        });

        foreach (DB::table('reservations')->orderBy('id')->get() as $row) {
            $updates = [];

            if (empty($row->public_id)) {
                $updates['public_id'] = (string) Str::ulid();
            }
            if (empty($row->reference)) {
                $updates['reference'] = sprintf('RRS-%s-%06d', now()->format('Y'), $row->id);
            }
            if (empty($row->purpose) && ! empty($row->title)) {
                $updates['purpose'] = $row->title;
            }
            if (empty($row->status)) {
                $updates['status'] = ReservationStatus::DRAFT->value;
            }
            if (empty($row->type)) {
                $updates['type'] = ReservationType::STANDARD->value;
            }

            if ($updates !== []) {
                DB::table('reservations')->where('id', $row->id)->update($updates);
            }
        }

    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropUnique(['reference']);
            $table->dropIndex(['requester_id', 'status']);
            $table->dropIndex(['location_id', 'status', 'reservation_date']);
            $table->dropConstrainedForeignId('requester_id');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn([
                'public_id',
                'reference',
                'type',
                'purpose',
                'attendee_count',
                'status',
                'submitted_at',
                'approved_at',
                'cancelled_at',
                'cancellation_reason',
                'version',
            ]);
        });
    }
};
