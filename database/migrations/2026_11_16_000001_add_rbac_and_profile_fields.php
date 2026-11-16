<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->json('permissions')->nullable()->after('is_system');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('mentor_id')->nullable()->after('level_id')->constrained('users')->nullOnDelete();
            $table->string('phone', 30)->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('avatar_color');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('link')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('link');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mentor_id');
            $table->dropColumn(['phone', 'is_active', 'last_login_at']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'permissions']);
        });
    }
};
