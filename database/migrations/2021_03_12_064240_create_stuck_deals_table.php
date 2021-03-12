<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStuckDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stuck_deals', function (Blueprint $table) {
            $table->id();
            $table->integer('dealId'); //id сделки, в коротой сформированы задачи
            $table->integer('taskId'); //id задачи, которая инициировала застревание
            $table->string('comment')->nullable(); //комментарий мастера
            $table->string('type'); //тип события empty/pause/alert
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
        Schema::dropIfExists('stuck_deals');
    }
}
