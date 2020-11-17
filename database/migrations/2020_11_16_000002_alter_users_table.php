<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (empty(env('FORCE_ALTER_USERS_TABLE'))) {
			return;
		}

        DB::unprepared('
ALTER TABLE users
CHANGE COLUMN name name varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
CHANGE COLUMN email email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('
ALTER TABLE users
CHANGE COLUMN name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
CHANGE COLUMN email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
		');
    }
}
