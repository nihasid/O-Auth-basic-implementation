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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('users_id')->nullable();
            $table->uuid('companies_id')->nullable();
            $table->string('notification_title', 250)->nullable();
            $table->boolean('status')->default(0);
            $table->boolean('email_sent_status')->default(0);
            $table->boolean('notification_status')->default(0);
            $table->timestamps();

            $table->foreign('users_id', 'notifications_users_id_foreign')->references('id')->on('users');
            $table->foreign('companies_id', 'notifications_companies_id_foreign')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
