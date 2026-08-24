<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_email');
            $table->enum('user_type', ['internal', 'external']);
            $table->string('phone')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('booking_reference')->unique();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'Confirmed', 'Cancelled', 'Completed', 'Pending_for_Verification', 'Requested_by_Guest', 'Rejected'])->default('Pending_for_Verification');
            $table->text('notes')->nullable();
            $table->json('confirmed_by_admins')->nullable();
            $table->timestamps();

            $table->index('user_email');
            $table->index('status');
            $table->index('booking_date');

            $table->index('user_id');
            $table->index('booking_reference');
            $table->index(['booking_date', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }

};
