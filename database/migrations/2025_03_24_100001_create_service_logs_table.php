<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crp_id');
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('service_type');
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->index('crp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
