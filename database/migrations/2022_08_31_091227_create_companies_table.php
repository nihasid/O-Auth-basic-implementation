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
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('company_type_id')->unique();
            $table->string('company_name', 255);
            $table->string('company_department', 255)->nullable();
            $table->string('short_description', 255)->nullable();
            $table->timestamp('company_started_at')->nullable();
            $table->timestamp('company_ended_at')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->unique(['company_type_id', 'company_name', 'company_department'], 'companies_index_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
