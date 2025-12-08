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
           Schema::create('documents', function (Blueprint $table) {

              $table->id();
              $table->string('candidate_id');
              $table->enum('type', ['resume', 'cover_letter', 'portfolio', 'other']);
              $table->text('name');
              $table->string('file_path');
              $table->string('mime_type');
              $table->text('extracted_text')->nullable();
              $table->text('notes')->nullable();
              $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
