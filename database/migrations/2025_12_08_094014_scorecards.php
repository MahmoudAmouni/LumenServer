<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorecards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('interview_id');
            $table->string('status')->default('pending');
            $table->integer('score_rate')->nullable();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('scorelabel_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorecards');
    }
};
