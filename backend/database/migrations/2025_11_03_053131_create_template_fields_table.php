<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('resource_templates')->onDelete('cascade');
            $table->string('field_name'); 
            $table->string('field_key'); 
            $table->enum('field_type', ['text', 'number', 'textarea', 'checkbox', 'image', 'dropdown']); 
            $table->boolean('is_required')->default(false);
            $table->integer('order_index')->default(0);
            $table->string('placeholder')->nullable();
            $table->text('default_value')->nullable();
            $table->text('metadata')->nullable(); // ADD THIS LINE
            $table->timestamps();
            
            $table->index('template_id');
            $table->index('order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};