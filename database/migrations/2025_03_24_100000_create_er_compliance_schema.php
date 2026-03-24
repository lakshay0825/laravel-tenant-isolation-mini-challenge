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
            $table->string('first_name');
            $table->string('last_name');
            $table->text('ssn');
            $table->text('dob');
            $table->string('state_code', 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('crp_id');
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->uuid('crp_id');
            $table->text('description');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->index('crp_id');
        });

        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2);
            $table->string('form_code');
            $table->string('version');
            $table->json('schema');
            $table->json('mapping')->nullable();
            $table->string('pdf_template_path')->nullable();
            $table->timestamps();
        });

        Schema::create('client_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->uuid('crp_id');
            $table->string('key');
            $table->text('value');
            $table->timestamps();

            $table->index(['client_id', 'key']);
            $table->index('crp_id');
        });

        Schema::create('service_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crp_id');
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->longText('notes_master');
            $table->string('narrative_hash')->nullable();
            $table->string('billing_status')->nullable();
            $table->string('invoice_number')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('crp_id');
        });

        Schema::create('note_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('service_log_id')->constrained('service_logs')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('data');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('service_log_id')->constrained('service_logs')->cascadeOnDelete();
            $table->uuid('crp_id');
            $table->string('type');
            $table->string('s3_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('crp_id');
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('crp_id');
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('template_id')->constrained('form_templates')->restrictOnDelete();
            $table->longText('form_data');
            $table->string('pdf_s3_key')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('crp_id');
        });

        Schema::create('crp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->nullable();
            $table->uuid('crp_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action_type', 32);
            $table->string('resource_type');
            $table->string('resource_id');
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->json('action_context')->nullable();
            $table->char('hash', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->index('crp_id');
            $table->index(['resource_type', 'resource_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crp_audit_logs');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('note_versions');
        Schema::dropIfExists('service_logs');
        Schema::dropIfExists('client_metadata');
        Schema::dropIfExists('form_templates');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('clients');
    }
};
