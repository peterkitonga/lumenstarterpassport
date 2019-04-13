<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('path')->nullable();
            $table->string('method',10)->nullable();
            $table->text('request')->nullable();
            $table->string('message', 300)->nullable();
            $table->integer('line')->nullable();
            $table->string('file')->nullable();
            $table->longText('trace')->nullable();
            $table->boolean('is_request')->default(1);
            $table->boolean('is_read')->default(0);
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
        Schema::dropIfExists('error_logs');
    }
}
