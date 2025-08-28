<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preview_data', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('status'); // pending, processing, completed, failed
            $table->string('title')->nullable();
            $table->string('thumbnail')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preview_data');
    }
};
