<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeProfileFieldsNullableInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->text('about')->nullable()->change();
            $table->boolean('vacation_mode')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->text('about')->nullable(false)->change();
            $table->boolean('vacation_mode')->nullable(false)->change();
        });
    }
}
