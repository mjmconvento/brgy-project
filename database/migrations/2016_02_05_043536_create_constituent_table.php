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
        Schema::create('constituents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('address');
            $table->timestamps();
        });
    }

    /**
     *
     * @return void
     */
    public function down()
    {  
        Schema::drop('constituents');
    }
}
