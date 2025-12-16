<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruiter_id');  
            $table->unsignedBigInteger('company_id');
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('level')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
