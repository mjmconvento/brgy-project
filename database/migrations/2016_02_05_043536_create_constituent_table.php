<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConstituentTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE constituent(
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80), 
            middle_name VARCHAR(80),
            last_name VARCHAR(80),
            address VARCHAR(255),
            brgy_captain_id INT,
            has_record BOOLEAN,
            has_unpaid_tax BOOLEAN,
            created_at DATETIME,
            updated_at DATETIME
        )');
    }

    /**
     *
     * @return void
     */
    public function down()
    {  
        Schema::drop('constituent');
    }
}
