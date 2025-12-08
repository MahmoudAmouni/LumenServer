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
            Schema::create('scorecards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('evakuater_id');
                $table->unsignedBigInteger('intreview_id');
                $table->string('status')->default('pending');
                $table->string('overall_recommendation')->nullable();
                $table->string('summary')->nullable();
                $table->timestamps();




            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scorecards');
    }
};
