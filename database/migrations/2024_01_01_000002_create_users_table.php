<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('full_name');
            $table->string('full_name_ar')->nullable();
            $table->string('password');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title')->nullable();
            $table->string('job_title_ar')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('preferred_language', 5)->default('en');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('ldap_guid')->unique()->nullable();
            $table->string('ldap_domain')->nullable();
            $table->unsignedSmallInteger('monthly_appreciation_limit')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'department_id']);
            $table->index('ldap_guid');
            $table->fullText(['full_name', 'username', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
