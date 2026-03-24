<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crp_id');
            $table->string('name');
            $table->text('ssn');
            $table->text('dob');
            $table->string('signature_path')->nullable();
            $table->timestamps();

            $table->index('crp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
