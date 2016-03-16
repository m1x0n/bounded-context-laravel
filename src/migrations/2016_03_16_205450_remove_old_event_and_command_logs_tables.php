<?php

use Illuminate\Database\Migrations\Migration;

class RemoveOldEventAndCommandLogsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('event_snapshot_log');
        Schema::drop('event_snapshot_stream');
        Schema::drop('command_snapshot_log');
        Schema::drop('command_snapshot_stream');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        throw new \Exception("This is a drop migration. Re-run migrations prior to this one to reset.");
    }
}
