<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCriminalRecordTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE criminal_record(
            id INT AUTO_INCREMENT PRIMARY KEY,
            constituent_id INT, 
            case_name VARCHAR(80),
            details LONGTEXT,
            execution_date DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (constituent_id) REFERENCES constituent(id) ON DELETE CASCADE
        )');
    }

    /**
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('criminal_record');
    }
}
