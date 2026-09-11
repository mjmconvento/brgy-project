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
        Schema::create('criminal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituent_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('case_name');
            $table->text('details')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['constituent_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criminal_records');
    }
};
