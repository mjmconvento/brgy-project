<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrgyCaptainsTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brgy_captain_candidate', function (Blueprint $table) {
            $table->engine = 'InnoDB';
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
        Schema::drop('brgy_captain_candidate');
    }
}
