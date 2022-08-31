<?php

namespace percipiolondon\shortlink\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;
use percipiolondon\shortlink\db\Table;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @var string|null
     */
    public string|null $driver = null;

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Refresh the db schema caches

        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();
        return true;
    }

    /**
     * @return bool
     */
    public function createTables(): bool
    {
        $tableRoutesCreated = false;
        $tableSchemaRoutes = Craft::$app->db->schema->getTableSchema(Table::ROUTES);

        if ($tableSchemaRoutes === null) {
            $this->createTable(Table::ROUTES, [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                // foreign keys
                'siteId' => $this->integer(),
                'ownerId' => $this->integer()->notNull(),
                // fields
                'shortlinkUri' => $this->string(255)->notNull(),
                'destination' => $this->string(255),
                'httpCode' => $this->string()->notNull(),
                'hitCount' => $this->integer()->defaultValue(0),
                'lastUsed' => $this->dateTime(),
                'status' => $this->enum('status', ['active', 'inactive']),
            ]);

            $tableRoutesCreated = true;
        }

        return $tableRoutesCreated;
    }

    /**
     *
     */
    public function createIndexes(): void
    {
        $this->createIndex(null, Table::ROUTES, 'siteId', false);
        $this->createIndex(null, Table::ROUTES, 'ownerId', false);
        $this->createIndex(null, Table::ROUTES, 'shortlinkUri', true);
        $this->createIndex(null, Table::ROUTES, 'destination', false);

    }

    /**
     *
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::ROUTES, 'siteId', \craft\db\Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ROUTES, 'ownerId', \craft\db\Table::ENTRIES, ['id'], 'CASCADE', 'CASCADE');
    }

    /**
     *
     */
    public function dropForeignKeys(): void
    {
        $tables = [
            'shortlink_routes'
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists('{{%' . $table . '}}')) {
                Db::dropAllForeignKeysToTable('{{%' . $table . '}}');
            }
        }
    }

    /**
     *
     */
    public function dropTables(): void
    {
        if (Craft::$app->db->schema->getTableSchema(Table::ROUTES)) {
            $this->dropTable(Table::ROUTES);
        }
    }
}
