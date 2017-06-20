<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVersionToPlayerSnapshots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('player_snapshots', function (Blueprint $table) {
            $table->renameColumn('version', 'update_count');
        });
        Schema::table('player_snapshots', function (Blueprint $table) {
            $table->integer('version')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('player_snapshots', function (Blueprint $table) {

            $table->dropColumn('version');
            $table->renameColumn('update_count', 'version');
        });
    }
}
