<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('item_code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_per_hour', 8, 2);
            $table->integer('available_quantity')->default(1);
            $table->enum('status', ['Available', 'Unavailable', 'Maintenance'])->default('Available');
            $table->timestamps();
            
            $table->index('item_code');
            $table->index('status');
        });
    }
    public function down()
    {
        Schema::dropIfExists('booking_items');
    }
};
