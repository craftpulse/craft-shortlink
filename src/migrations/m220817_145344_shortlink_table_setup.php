<?php

namespace percipiolondon\shortlink\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;
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
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndex();
            $this->createForeignKeys();
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
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
        $tableRequestCreated = false;
        $tableStaticRequestCreated = false;
        $tableSchemaRedirects = Craft::$app->db->schema->getTableSchema(Table::REDIRECTS);
        $tableSchemaStaticRedirects = Craft::$app->db->schema->getTableSchema(Table::STATIC_REDIRECTS);

        if ($tableSchemaRedirects === null) {
            $this->createTable(Table::REDIRECTS, [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                //foreign keys
                'siteId' => $this->integer(),
                'associatedElementId' => $this->integer()->notNull(),
                //fields
                'shortlinkSlug' => $this->string(255)->notNull(),
                'shortlinkHttpCode' => $this->string()->notNull(),
                'shortlinkHitCount' => $this->integer()->defaultValue(0),
                'shortlinkHitLast' => $this->dateTime()
            ]);

            $tableRequestCreated = true;
        }

        if ($tableSchemaStaticRedirects === null) {
            $this->createTable(Table::STATIC_REDIRECTS, [
                'id' => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                //foreign keys
                'siteId' => $this->integer(),
                //fields
                'shortlinkSlug' => $this->string(255)->notNull(),
                'shortlinkDestination' => $this->string(255)->notNull(),
                'shortlinkHttpCode' => $this->string()->notNull(),
                'shortlinkHitCount' => $this->integer()->defaultValue(0),
                'shortlinkHitLast' => $this->dateTime()
            ]);

            $tableRequestCreated = true;
        }

        return $tableRequestCreated && $tableStaticRequestCreated;
    }

    /**
     *
     */
    public function createIndexes(): void
    {
        $this->createIndex(null, Table::REDIRECTS, 'siteId', false);
        $this->createIndex(null, Table::REDIRECTS, 'associatedElementId', false);
        $this->createIndex(null, Table::REDIRECTS, 'shortlinkSlug', true);
        $this->createIndex(null, Table::STATIC_REDIRECTS, 'siteId', false);
        $this->createIndex(null, Table::STATIC_REDIRECTS, 'shortlinkSlug', true);
        $this->createIndex(null, Table::STATIC_REDIRECTS, 'shortlinkDestination', false);
    }

    /**
     *
     */
    public function createForeignKeys(): void
    {
        $this->addForeignKey(null, Table::REDIRECTS, 'siteId', \craft\db\Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::REDIRECTS, 'associatedElementId', \craft\db\Table::ENTRIES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::STATIC_REDIRECTS, 'siteId', \craft\db\Table::SITES, ['id'], 'CASCADE', 'CASCADE');
    }

    /**
     *
     */
    public function dropForeignKeys(): void
    {
        $tables = [
            'shortlink_redirect',
            'shortlink_static_redirect'
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
        if (Craft::$app->db->schema->getTableSchema(Table::REDIRECTS)) {
            $this->dropTable(Table::REDIRECTS);
        }

        if (Craft::$app->db->schema->getTableSchema(Table::STATIC_REDIRECTS)) {
            $this->dropTable(Table::STATIC_REDIRECTS);
        }
    }
}
