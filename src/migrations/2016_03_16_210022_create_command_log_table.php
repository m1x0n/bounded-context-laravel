<?php

use Illuminate\Database\Migrations\Migration;

class CreateCommandLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE `command_log` (
            `id` binary(16) NOT NULL DEFAULT '0000000000000000',
            `order` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
            `snapshot` mediumtext COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`order`)
          ) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;     
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Scheme::drop("command_log");
    }
}
