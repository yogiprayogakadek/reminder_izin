<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('permit_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('number')->nullable();
            $table->string('issuer');
            $table->date('issued_at')->nullable();
            $table->date('expires_at');
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminder_enabled')->default(true);
            $table->date('reminder_paused_until')->nullable();
            $table->timestamp('renewal_started_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expires_at');
            $table->index(['category_id', 'expires_at']);
            $table->index('pic_user_id');
        });

        Schema::create('permit_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained()->cascadeOnDelete();
            $table->date('old_issued_at')->nullable();
            $table->date('old_expires_at');
            $table->date('new_issued_at')->nullable();
            $table->date('new_expires_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('permit_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permit_renewal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('permit_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permit_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_recipients');
        Schema::dropIfExists('permit_files');
        Schema::dropIfExists('permit_renewals');
        Schema::dropIfExists('permits');
    }
};
