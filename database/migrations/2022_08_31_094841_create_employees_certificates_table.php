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
        Schema::create('employees_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employees_id');
            $table->string('certificate');
            $table->boolean('status')->default(0);
            $table->timestamp('certificate_created_at')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamps();
            $table->foreign('employees_id', 'employees_certificates_employee_id_foreign')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees_certificates');
    }
};
