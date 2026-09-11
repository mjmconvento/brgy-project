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
        Schema::create('barangay_captains', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            // Structured address. `house_number` is nullable because rural and
            // informal addresses frequently do not have one.
            $table->string('house_number', 30)->nullable();
            $table->string('street', 120);
            $table->string('barangay', 120);
            $table->string('city', 120);
            $table->string('country', 120)->default('Philippines');

            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index(['city', 'barangay']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangay_captains');
    }
};
