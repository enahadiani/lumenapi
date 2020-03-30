<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hakakses', function (Blueprint $table) {
            // $table->bigIncrements('id');
            $table->string('kode_klp_menu',10);
            $table->string('nik',10)->unique()->notNullable();
            $table->string('nama',100);
            $table->string('pass',10);
            $table->string('status_admin',1);
            $table->string('kode_lokasi',10)->notNullable();
            $table->string('klp_akses',20);
            $table->string('menu_mobile',50);
            $table->string('path_view',50);
            $table->string('kode_menu_lab',20);
            $table->string('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hakakses');
    }
}
