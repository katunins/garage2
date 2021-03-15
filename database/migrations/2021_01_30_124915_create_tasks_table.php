<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('master');
            $table->integer('templateid')->nullable(); //шаблон, по которому создана задача
            $table->float('time'); //min
            $table->string('status'); //temp временная, wait ожитает выполнения, finished выполнена, repair зависла
            $table->text('mastercomment')->nullable();
            $table->integer('taskidbefore')->nullable(); //предварительная задача
            $table->dateTime('start');
            $table->dateTime('end');
            $table->integer('buffer')->nullable(); //задержка в минутах
            $table->integer('line'); //линия параллельного производства
            $table->integer('position')->nullable(); //позиция в линии
            $table->string('generalinfo');
            $table->text('info')->nullable();

            $table->integer('dealid')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('managernote')->default(false);

            $table->string('deal')->nullable();
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
        Schema::dropIfExists('tasks');
    }
}
