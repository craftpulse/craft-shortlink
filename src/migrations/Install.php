<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\migrations;

use Craft;
use craft\db\Migration;
use craft\fieldlayoutelements\TextField;
use craft\helpers\Db;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\records\FieldLayout as FieldLayoutRecord;

use craftpulse\shortlink\Shortlink;
use craftpulse\shortlink\elements\Route as RouteElement;
use craftpulse\shortlink\records\RouteRecord;
use craftpulse\shortlink\services\Routes;

use yii\base\Exception;

/**
 * @author    CraftPulse
 * @package   Shortlink
 *
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var ?string The database driver to use
     */
    public ?string $driver = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->addForeignKeys();
            $this->addFieldLayout();

            // Refresh the db schema caches
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
        Craft::$app->getFields()->deleteLayoutsByType(RouteElement::class);

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables.
     *
     * @return bool
     * @throws Exception
     */
    protected function createTables(): bool
    {
        if(!$this->db->tableExists(RouteRecord::tableName())) {
            $this->createTable(
                '{{%shortlink_routes}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'fieldLayoutId' => $this->integer(),

                    // data
                    'origin' => $this->string(),
                    'uriPattern' => $this->string(),
                    'destination' => $this->string(),
                    'matchType' => $this->string()->defaultValue('exact'),
                    'httpCode' => $this->integer()->defaultValue(301),
                    'hitCount' => $this->integer()->defaultValue(0),
                    'lastUsed' => $this->dateTime(),
                ]
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(
            null,
            '{{%shortlink_routes}}',
            'id',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );
    }

    public function addFieldLayout(): void
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(RouteElement::class) ?? new FieldLayout();

        $tab = new FieldLayoutTab(['name' => 'Route']);
        $tab->setLayout($fieldLayout);

        $tab->setElements(Shortlink::$plugin->routes->createFields());
        $fieldLayout->setTabs([$tab]);

        Craft::$app->getFields()->saveLayout($fieldLayout);
    }

    /**
     * @inheritdoc
     */
    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%shortlink_routes}}')) {
            Db::dropAllForeignKeysToTable('{{%shortlink_routes}}');
        }
    }

    /**
     * @inheritdoc
     */
    public function dropTables(): void
    {
        if (Craft::$app->db->schema->getTableSchema('{{%shortlink_routes}}')) {
            $this->dropTable('{{%shortlink_routes}}');
        }
    }

}
