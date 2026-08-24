<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resource_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->onDelete('cascade');
            $table->smallInteger('day_of_week'); // 1=Monday, 2=Tuesday, ..., 7=Sunday
            $table->string('day_name'); // For readability: Monday, Tuesday, etc.
            $table->boolean('is_available')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            
            $table->unique(['resource_id', 'day_of_week']);
            
            $table->index(['resource_id', 'is_available']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('resource_availabilities');
    }
};
