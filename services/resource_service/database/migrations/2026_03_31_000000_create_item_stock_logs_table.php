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
        Schema::create('item_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // ID of the booking_item
            $table->unsignedBigInteger('booking_id'); // ID from the booking service
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('booking_items')->onDelete('cascade');
            $table->index(['item_id', 'date']);
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_stock_logs');
    }
};
