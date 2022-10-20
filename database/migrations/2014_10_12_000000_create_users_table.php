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
        
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->datetime('date_of_birth')->nullable();
            $table->boolean('is_active')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->string('thumbnail', 191)->nullable();
            $table->uuid('company_id')->nullable();
			$table->integer('updated_by')->nullable();
            $table->foreign('company_id', 'users_company_id_foreign')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
