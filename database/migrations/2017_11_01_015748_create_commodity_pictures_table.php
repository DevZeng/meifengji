<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommodityPicturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commodity_pictures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('commodity_id');
            $table->unsignedInteger('product_id');
            $table->string('url');
            $table->string('thumb_url');
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
        Schema::dropIfExists('commodity_pictures');
    }
}
