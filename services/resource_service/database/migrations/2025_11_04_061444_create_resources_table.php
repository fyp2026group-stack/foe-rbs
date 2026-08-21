<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('resources', function (Blueprint $table) {
                $table->id();
                $table->string('name'); 
                $table->string('location_name');
                $table->text('description')->nullable();
                $table->decimal('base_price', 8, 2);
                $table->foreignId('category_id')->constrained()->onDelete('restrict');
                $table->foreignId('template_id')->nullable()->constrained('resource_templates')->onDelete('set null');
                $table->json('template_data')->nullable();
                $table->unsignedBigInteger('assigned_admin_id')->nullable();
                $table->json('assigned_admin_ids')->nullable();
                $table->string('department')->nullable();
                $table->enum('status', ['Active', 'Inactive', 'Maintenance'])->default('Active');
                $table->timestamps();

                $table->index('category_id');
                $table->index('status');
                $table->index('department');
                $table->index('assigned_admin_id');
                $table->index('template_id');
            });
        }
        public function down(): void
        {
            Schema::dropIfExists('resources');
        }
    };
