<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('status')->default('draft');
            $table->text('summary_note')->nullable();
            $table->timestamp('morning_submitted_at')->nullable();
            $table->timestamp('summary_submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'report_date']);
            $table->index(['report_date', 'status']);
        });

        Schema::table('agendas', function (Blueprint $table) {
            $table->foreignId('daily_report_id')->nullable()->after('user_id')->constrained('daily_reports')->cascadeOnDelete();
            $table->string('priority')->default('medium')->after('end_time');
            $table->timestamp('completed_at')->nullable()->after('summary_submitted_at');
            $table->unsignedInteger('sort_order')->default(0)->after('agenda_date');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_report_id');
            $table->dropColumn(['priority', 'completed_at', 'sort_order']);
        });

        Schema::dropIfExists('daily_reports');
    }
};
