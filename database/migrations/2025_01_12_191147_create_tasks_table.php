<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // کلید خارجی به جدول users
            $table->string('task'); // متن تسک
            $table->boolean('completed')->default(false); // وضعیت تسک
            $table->timestamps(); // تاریخ ایجاد و بروزرسانی

            // تعریف کلید خارجی
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
