<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('inquiries', 'response')) {
                $table->longText('response')->nullable();
            }

            if (!Schema::hasColumn('inquiries', 'responded_at')) {
                $table->timestamp('responded_at')->nullable();
            }

            if (!Schema::hasColumn('inquiries', 'responded_by')) {
                $table->unsignedBigInteger('responded_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            foreach (['response', 'responded_at', 'responded_by'] as $column) {
                if (Schema::hasColumn('inquiries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};