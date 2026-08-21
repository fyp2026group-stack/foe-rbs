<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->enum('item_type', ['resource', 'booking_item']);
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price_per_hour', 8, 2);
            $table->decimal('hours', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
            
            $table->index(['booking_id', 'item_type']);
            $table->index('item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_details');
    }
};
