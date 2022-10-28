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
        Schema::table('employees_certificates', function (Blueprint $table) {
            //

            $table->uuid('duties_id')->after('employees_id');
            $table->foreign('duties_id', 'duties_employees_duties_id_foreign')->references('id')->on('duties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_certificates', function (Blueprint $table) {
            //
            $table->dropColumn('duties_id');
        });
    }
};
