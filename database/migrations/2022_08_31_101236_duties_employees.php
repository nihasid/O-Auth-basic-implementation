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
        //
        if(!Schema::hasTable('duties_employees')) 
        {
            Schema::create('duties_employees', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('employees_id');
                $table->uuid('duties_id');
                $table->timestamp('enrolled_date_started_at')->nullable();
                $table->timestamp('enrolled_date_ended_at')->nullable();
                $table->timestamps();
                $table->boolean('status')->default(0);
                $table->foreign('employees_id', 'duties_employees_employees_id_foreign')->references('id')->on('employees');
                $table->foreign('duties_id', 'duties_employees_duties_id_foreign')->references('id')->on('duties');
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
        //

        Schema::dropIfExists('duties_employees');
    }
};
