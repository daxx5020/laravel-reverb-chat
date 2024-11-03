<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mediaable_id'); // ID of the related model (e.g., a Message ID)
            $table->string('mediaable_type');           // Type of the related model (e.g., "App\\Models\\Message")
            $table->string('file_path');                // Path to the media file
            $table->string('file_type')->nullable();    // Category of media (e.g., image, video, document)
            $table->string('mime_type')->nullable();    // MIME type of the file (e.g., "image/jpeg")
            $table->integer('size')->nullable();        // File size in bytes
            $table->timestamps();

            $table->index(['mediaable_id', 'mediaable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
