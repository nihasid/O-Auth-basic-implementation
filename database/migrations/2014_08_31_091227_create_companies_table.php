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
            $table->string('business_type')->nullable();
            $table->string('company_name', 255);
            $table->string('company_department', 255)->nullable();
            $table->string('short_description', 255)->nullable();
            $table->string('company_ceo_name', 255)->nullable();
            $table->timestamp('company_started_at')->nullable();
            $table->timestamp('company_ended_at')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('is_invite')->default(0);
            $table->boolean('is_share')->default(0);
            $table->timestamps();
            $table->unique(['business_type', 'company_name'], 'companies_index_unique');
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
