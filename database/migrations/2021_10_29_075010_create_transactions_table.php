<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('buyer_id')->nullable();
            $table->unsignedInteger('seller_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price');
            $table->unsignedInteger('total');
            $table->boolean('payment_status')->default(false);
            $table->boolean('status')->default(false);
            $table->string('payment_file')->nullable();
            $table->string('product_file')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
