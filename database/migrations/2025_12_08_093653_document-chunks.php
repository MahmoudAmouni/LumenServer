<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
           Schema::create('document_chunks', function (Blueprint $table) {
              $table->id();
              $table->unsignedBigInteger('document_id');
              $table->text('chunk_text');
              $table->string('embedding')->nullable();
              $table->integer('chunk_index');
              $table->integer('page_number')->nullable();
              $table->text('section')->nullable();
              $table->integer('token_count')->nullable();
              $table->timestamps();




            });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
