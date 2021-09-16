<?php
/**
 * craft-shortlink plugin for Craft CMS 3.x
 *
 * A plugin to use your own subdomain as a url shortener
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 Percipio
 */

namespace percipioglobal\craftshortlink;

use percipioglobal\craftshortlink\services\CraftshortlinkService;
use percipioglobal\craftshortlink\variables\CraftshortlinkVariable;
use percipioglobal\craftshortlink\models\Settings;
use percipioglobal\craftshortlink\fields\CraftshortlinkField;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class Craftshortlink
 *
 * @author    Percipio
 * @package   Craftshortlink
 * @since     1.0.0
 *
 * @property  CraftshortlinkService $craftshortlinkService
 */
class Craftshortlink extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Craftshortlink
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.1';

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CraftshortlinkField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('craftshortlink', CraftshortlinkVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'craft-shortlink',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }


    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'craft-shortlink/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
