<?php

namespace percipiolondon\shortlink;

use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use nystudio107\pluginvite\services\VitePluginService;
use percipiolondon\shortlink\assetbundles\shortlink\ShortlinkAsset;
use percipiolondon\shortlink\models\SettingsModel as Settings;
use percipiolondon\shortlink\variables\ShortlinkVariable;
use yii\base\event;

/**
*
* @author    percipiolondon
* @package   Shortlink
* @since     1.0.0
* @property VitePluginService  $vite
* @property TimeloopService $timeloop
*
*/

class Shortlink extends Plugin
{
    // Static Properties
    // =================

    /**
     * @var Shortlink|null
     */
    public static ?Shortlink $plugin;

    /**
     * @var ShortlinkVariable|null
     */
    public static ?ShortlinkVariable $shortlinkVariable = null;

    /**
     * @var Settings|null
     */
    public static ?Settings $settings = null;

    // Public Properties
    // =================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool
     */
    public bool $hasCpSection = true;

    // Static Methods
    // ==============

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        $config['components'] = [
            'shortlink' => __CLASS__,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => ShortlinkAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3751',
                'serverPublic' => 'http://localhost:3700',
                'errorEntry' => '/src/js/shortlink.ts',
                'devServerInternal' => 'http://craft-shortlink-buildchain:3751',
                'checkDevServer' => true,
            ],
        ];

        parent::__construct($id, $parent, $config);
    }

    // Public Methods
    // ==============

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('shortlink', [
                    'class' => ShortlinkVariable::class,
                    'viteService' => $this->vite,
                ]);
            }
        );

        Craft::info(
            Craft::t(
                'shortlink',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }
}