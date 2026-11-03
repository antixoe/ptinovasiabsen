<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('level_id')->nullable()->after('role_id')->constrained('levels')->nullOnDelete();
            $table->string('department')->nullable()->after('email');
            $table->string('avatar_color')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn(['department', 'avatar_color']);
        });
    }
};
