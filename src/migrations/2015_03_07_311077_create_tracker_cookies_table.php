<?php

use PragmaRX\Tracker\Support\Migration;

class CreateTrackerCookiesTable extends Migration
{    
    private $table = 'tracker_cookies';

    
    public function migrateUp()
    {
        $this->builder->create(
            $this->table,
            function ($table) {
                $table->bigIncrements('id');
                $table->string('uuid')->unique();
                $table->string('cookie_data')->nullable();
                $table->timestamps();
                $table->index('created_at');
                $table->index('updated_at');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function migrateDown()
    {
        $this->drop($this->table);
    }
}
