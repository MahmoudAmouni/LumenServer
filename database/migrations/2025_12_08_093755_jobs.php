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
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->string('location')->nullable();
                $table->string('employment_type')->nullable();
                $table->string('level')->nullable();
                $table->integer('hiring_manager_id')->nullable();  
                $table->integer('company_id')->nullable();          
                $table->string('status')->default('open');
                $table->timestamps();
     });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
