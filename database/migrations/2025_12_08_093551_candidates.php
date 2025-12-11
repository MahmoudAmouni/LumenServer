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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruiter_id');
            $table->string('full_name');
            $table->integer('age')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('location')->nullable();
            $table->string('level');
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('cv_path')->nullable();
            $table->timestamps();        
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
