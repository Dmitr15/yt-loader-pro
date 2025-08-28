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
        Schema::create('preview_subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preview_data_id')->constrained('preview_data')->onDelete('cascade');
            $table->string('type'); // 'subs' или 'captions'
            $table->string('lang_code'); // код языка (например, 'en', 'ru')
            $table->string('lang_name');
            $table->timestamps();

            $table->index('preview_data_id');
            $table->index(['preview_data_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preview_subtitles');
    }
};
