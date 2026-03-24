<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('goal_id');
            $table->timestamp('ended_at')->nullable()->after('started_at');

            $table->index(['staff_id', 'started_at', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            $table->dropIndex(['staff_id', 'started_at', 'ended_at']);
            $table->dropColumn(['started_at', 'ended_at']);
        });
    }
};
