<?php

namespace percipiolondon\shortlink;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
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

    /**
     * @var View|null
     */
    public static ?View $view = null;

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

        // Initialize properties
        self::$settings = self::$plugin->getSettings();
        self::$view = Craft::$app->getView();

        $this->name = self::$settings->pluginName;

        // Install event listeners
        $this->installEventListeners();

        // Register variables
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

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('shortlink/plugin'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        /** @var User $currentUser */
        $request = Craft::$app->getRequest();
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Only show sub navigation the user has permission to view
        if ($currentUser->can('shortlink:dashboard')) {
            $subNavs['dashboard'] = [
                'label' => Craft::t('shortlink', 'Dashboard'),
                'url' => 'shortlink/dashboard',
            ];
        }

        $editableSettings = true;
        // check against allowAdminChanges
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $editableSettings = false;
        }

        if ($editableSettings && $currentUser->can('shortlink:plugin-settings')) {
            $subNavs['plugin'] = [
                'label' => Craft::t('shortlink', 'Plugin settings'),
                'url' => 'shortlink/plugin'
            ];
        }

        return array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
    }

    // Protected Methods
    // =================

    protected function installEventListeners()
    {
        $request = Craft::$app->getRequest();
        // Install our event listeners
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            $this->installCpEventListeners();;
        }
    }

    protected function installCpEventListeners(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                Craft::debug(
                    'UrlManager::EVENT_REGISTER_CP_URL_RULES',
                    __METHOD__
                );
                // Register our control panel routes
                $event->rules = array_merge(
                    $event->rules,
                    $this->customAdminCpRoutes()
                );
            }
        );

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                Craft::debug(
                    'UserPermissions::EVENT_REGISTER_PERMISSIONS',
                    __METHOD__
                );
                // Register our custom permissions
                $event->permissions[Craft::t('shortlink', 'Shortlink')] = $this->customAdminCpPermissions();
            }
        );
    }

    /**
     * Return the custom Control Panel routes
     *
     * @return array
     */
    protected function customAdminCpRoutes(): array
    {
        return [
            'shortlink' => 'shortlink/settings/dashboard',
            'shortlink/dashboard' => 'shortlink/settings/dashboard',
            'shortlink/plugin' => 'shortlink/settings/plugin',
        ];
    }

    /**
     * Return the custom Control Panel user permissions.
     *
     * @return array
     */
    protected function customAdminCpPermissions(): array
    {
        return [
            'shortlink:dashboard' => [
                'label' => Craft::t('shortlink', 'Dashboard'),
            ],
            'shortlink:plugin-settings' => [
                'label' => Craft::t('shortlink', 'Edit Plugin Settings'),
            ]
        ];
    }
}
