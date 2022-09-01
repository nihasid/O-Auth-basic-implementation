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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('gender');
            $table->string('date_of_birth');
            $table->uuid('company_id');
            $table->uuid('position_id');
            $table->timestamp('emp_started_period')->nullable();
            $table->timestamp('emp_ended_period')->nullable();
            $table->timestamp('signature_date')->useCurrent();
            $table->boolean('is_active')->default(0);
            $table->timestamps();
            $table->foreign('company_id', 'employees_company_id_foreign')->references('id')->on('companies');
            $table->foreign('position_id', 'employees_position_id_foreign')->references('id')->on('positions');
        });
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
