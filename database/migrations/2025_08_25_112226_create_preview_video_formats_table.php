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
        Schema::create('preview_video_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preview_data_id')->constrained('preview_data')->onDelete('cascade');
            $table->string('format_id'); // id_v из ваших данных
            $table->string('ext')->nullable();
            $table->string('filesize')->nullable();
            $table->string('codec')->nullable();
            $table->string('fps')->nullable();
            $table->string('tbr')->nullable();
            $table->string('vbr')->nullable();
            $table->string('asr')->nullable();
            $table->string('dynamic_range')->nullable();
            $table->string('resolution')->nullable();
            $table->string('format_note')->nullable();
            $table->timestamps();

            $table->index('preview_data_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preview_video_formats');
    }
};
