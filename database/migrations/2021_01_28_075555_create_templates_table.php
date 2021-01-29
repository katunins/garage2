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
            $table->string('masters'); //ID мастера '1/2/3'
            $table->string('params')->nullable(); //[Формат, Тип печати]
            $table->integer('buffer')->nullable(); //min
            $table->integer('producttime')->nullable(); //min - базовое время выполнения одной задачи
            $table->float('paramtime')->nullable(); //мин - расчетное время на расчетную еденицу (1кв. см к примеру)
            $table->string('period1')->nullable(); //доступные период 9:00-12:00
            $table->string('period2')->nullable(); 
            $table->string('condition1')->nullable(); // условие Формат=20х20/30х30
            $table->string('condition2')->nullable(); 
            $table->string('condition3')->nullable(); 
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
