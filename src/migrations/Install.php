<?php

namespace percipiolondon\shortlink\migrations;

use Craft;
use craft\db\migration;


class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // remove tables on safeDown
    }

    /**
     * Creates the tables needed for the Records used by the plugin
     */
    /**protected function createTables(): bool
    {

    }*/
}
