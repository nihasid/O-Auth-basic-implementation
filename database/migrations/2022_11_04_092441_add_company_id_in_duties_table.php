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
        Schema::table('duties', function (Blueprint $table) {
            //
            $table->uuid('company_id')->nullable()->after('id');
            $table->foreign('company_id', 'duties_company_id_foreign')->references('id')->on('companies');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('duties', function (Blueprint $table) {
            //
            $table->drop('company_id');

        });
    }
};
