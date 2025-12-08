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
          Schema::create('intreviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('interviewer_id');
                $table->unsignedBigInteger('intreview_type_id');
                $table->string('scorecard_id');
                $table->string('notes')->nullable();
                $table->integer('duration')->nullable();
                $table->dateTime('scheduled_at');
                $table->string('status')->default('scheduled');
                $table->text('feedback')->nullable();
                $table->timestamps();




            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intreviews');
    }
};
