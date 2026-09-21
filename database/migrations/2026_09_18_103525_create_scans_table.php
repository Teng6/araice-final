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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->nullable()->constrained('farmer_profiles')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('scan_type');
            $table->foreignId('disease_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('rice_varieties')->nullOnDelete();
            $table->foreignId('outbreak_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_url');
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('raw_predictions')->nullable();
            $table->string('status');
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_long', 10, 7)->nullable();
            $table->timestamp('scan_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
