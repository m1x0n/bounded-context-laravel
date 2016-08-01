<?php

use Illuminate\Database\Migrations\Migration;

use BoundedContext\Laravel\Illuminate\Log\Event;

class CreateEventLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = config('event_logs.table_name');
        DB::statement("
            CREATE TABLE `$table` (
            `id` binary(16) NOT NULL DEFAULT '0000000000000000',
            `order` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
            `aggregate_id` binary(16) NOT NULL DEFAULT '0000000000000000',
            `aggregate_type_id` binary(16) NOT NULL DEFAULT '0000000000000000',
            `snapshot` mediumtext COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`order`),
            KEY `id` (`id`),
            KEY `aggregate_id` (`aggregate_id`,`aggregate_type_id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=364 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;        
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = config('event_logs.table_name');
        Scheme::drop($table);
    }
}
