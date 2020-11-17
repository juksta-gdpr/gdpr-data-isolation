<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalInformationLookupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql_gdpr')->unprepared('
CREATE TABLE IF NOT EXISTS personal_information_lookup (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  token CHAR(36) NOT NULL,
  person_id INT NOT NULL,
  personal_information_id INT UNSIGNED NOT NULL,
  deleted TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX fk_personal_information_lookup_1_idx (personal_information_id ASC),
  INDEX token_idx (token ASC),
  INDEX person_idx (person_id ASC),
  CONSTRAINT fk_personal_information_lookup_1
    FOREIGN KEY (personal_information_id)
    REFERENCES personal_information (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_gdpr')->dropIfExists('personal_information_lookup');
    }
}
