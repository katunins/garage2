<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('productid'); //название продукта (Фотокнига)
            $table->integer('line'); //позиция в конвеере - 1
            $table->integer('position'); // позиция в линии
            $table->integer('taskidbefore')->nullable(); //задача, после которой ставим эту задачу
            $table->string('taskname'); // название задачи
            $table->json('masters'); //ID мастера '1/2/3'
            $table->json('miniparams')->nullable(); //[Формат, Тип печати]
            $table->integer('buffer')->nullable(); //min
            $table->integer('producttime')->nullable(); //min - базовое время выполнения одной задачи
            $table->float('paramtime')->nullable(); //мин - расчетное время на расчетную еденицу (1кв. см к примеру)
            $table->json('periods')->nullable(); //доступные период 9:00-12:00
            $table->json('conditions')->nullable(); // условие Формат=20х20/30х30
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
        Schema::dropIfExists('templates');
    }
}
