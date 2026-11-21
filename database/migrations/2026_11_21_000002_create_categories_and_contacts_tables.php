<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_e164', 20)->unique();
            $table->string('label', 60)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#0369A1');
            $table->unsignedSmallInteger('default_validity_months')->nullable();
            $table->foreignId('default_pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permit_category_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('permit_categories')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_category_recipients');
        Schema::dropIfExists('permit_categories');
        Schema::dropIfExists('contacts');
    }
};
