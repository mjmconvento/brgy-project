<?php

use App\Enums\TaxStatus;
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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituent_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('payment_month');
            $table->unsignedSmallInteger('payment_year');
            $table->string('status')->default(TaxStatus::Unpaid->value);
            $table->timestamps();

            // One tax record per constituent per billing period.
            $table->unique(['constituent_id', 'payment_year', 'payment_month'], 'taxes_period_unique');
            $table->index(['constituent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
