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
         Schema::create('copilot_queries', function (Blueprint $table) {
           $table->id();
           $table->string('candidate_id');
           $table->string('job_id');
              $table->text('query_text');
              $table->text('response_text');
              $table->string('query_by_recruiter_id');
              $table->text('citation_text')->nullable();
              $table->string('source_id');
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copilot_queries');
    }
};
