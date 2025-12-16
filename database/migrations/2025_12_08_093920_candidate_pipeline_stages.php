<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
           Schema::create('candidate_pipeline_stages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('pipeline_stage_id');
                $table->unsignedBigInteger('job_id');
                $table->string('notes')->nullable();
                $table->dateTime('moved_at')->nullable();
                $table->timestamps();
 



            });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_pipeline_stages');
    }
};
