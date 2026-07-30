<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $type = 'bigint';
        $unsigned = true;

        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM users WHERE Field = 'id'");
            if (!empty($columnInfo)) {
                $rawType = strtolower($columnInfo[0]->Type);
                if (str_contains($rawType, 'bigint')) {
                    $type = 'bigint';
                } else {
                    $type = 'int';
                }
                if (!str_contains($rawType, 'unsigned')) {
                    $unsigned = false;
                }
            }
        } catch (\Throwable $e) {
            // Fallback default to Laravel standard defaults
        }

        Schema::create('activity_logs', function (Blueprint $table) use ($type, $unsigned) {
            $table->id();
            
            if ($type === 'bigint') {
                if ($unsigned) {
                    $table->unsignedBigInteger('user_id')->nullable();
                } else {
                    $table->bigInteger('user_id')->nullable();
                }
            } else {
                if ($unsigned) {
                    $table->unsignedInteger('user_id')->nullable();
                } else {
                    $table->integer('user_id')->nullable();
                }
            }

            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
