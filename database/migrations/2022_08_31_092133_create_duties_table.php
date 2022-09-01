<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('duties')) 
        {
            Schema::create('duties', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->integer('duty_type_group');
                $table->string('duty_type_group_name')->nullable();
                $table->string('duty_group_detail')->nullable();
                $table->boolean('status')->default(0);
                $table->timestamps();
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('duties');
    }
};
