<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE tax(
            id INT AUTO_INCREMENT PRIMARY KEY,
            constituent_id INT, 
            amount DOUBLE,
            payment_month VARCHAR(80),
            payment_year VARCHAR(80),
            status VARCHAR(80),
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (constituent_id) REFERENCES constituent(id) ON DELETE CASCADE
        )');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tax');
    }
}
