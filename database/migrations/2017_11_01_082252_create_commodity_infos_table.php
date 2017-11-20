<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommodityInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commodity_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('unit')->nullable();
            $table->string('description',1000);
            $table->text('content');
            $table->unsignedInteger('sales')->default(0);
//            $table->float('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commodity_infos');
    }
}
