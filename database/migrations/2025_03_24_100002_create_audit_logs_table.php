<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crp_id');
            $table->string('auditable_type');
            $table->uuid('auditable_id');
            $table->string('event', 32);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index('crp_id');
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
