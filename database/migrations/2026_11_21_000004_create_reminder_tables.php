<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('permit_categories')->cascadeOnDelete();
            $table->smallInteger('offset_days');
            $table->time('send_time')->default('08:00:00');
            $table->unsignedSmallInteger('repeat_overdue_days')->nullable();
            $table->unsignedSmallInteger('max_repeats')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'offset_days']);
        });

        Schema::create('reminder_rule_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['reminder_rule_id', 'contact_id']);
        });

        Schema::create('reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('permit_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reminder_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reminder_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cycle_key', 80);
            $table->string('recipient_phone', 20)->nullable();
            $table->text('message_body')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('waha_message_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('idempotency_key', 160)->unique();
            $table->boolean('is_manual')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['permit_id', 'cycle_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');
        Schema::dropIfExists('reminder_templates');
        Schema::dropIfExists('reminder_rule_recipients');
        Schema::dropIfExists('reminder_rules');
    }
};
