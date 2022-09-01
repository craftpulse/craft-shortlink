<?php

namespace percipiolondon\shortlink;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\DefineHtmlEvent;
use craft\events\ModelEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use nystudio107\pluginvite\services\VitePluginService;
use percipiolondon\shortlink\assetbundles\shortlink\ShortlinkAsset;
use percipiolondon\shortlink\helpers\PluginTemplate;
use percipiolondon\shortlink\models\SettingsModel as Settings;
use percipiolondon\shortlink\services\ShortlinkService;
use percipiolondon\shortlink\variables\ShortlinkVariable;
use yii\base\event;

/**
*
* @author    percipiolondon
* @package   ShortlinkElement
* @since     1.0.0
*
* @property ShortlinkService $shortlinkService
* @property VitePluginService  $vite
* @property Settings $settings
*
*/

class Shortlink extends Plugin
{
    protected const SHORTLINK_PREVIEW_PATH = 'shortlink/sidebar/preview-shortlink';

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
            'shortlinks' => ShortlinkService::class,
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

        // Install global listeners
        $this->installGlobalEventListeners();

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
            $this->installCpEventListeners();
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
                $event->permissions[] = [
                    'heading' => Craft::t('shortlink', 'ShortlinkElement'),
                    'permissions' => $this->customAdminCpPermissions()
                ];
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event) {
                Craft::debug(
                    'Entry::EVENT_DEFINE_SIDEBAR_HTML',
                    __METHOD__
                );
                /* @var Entry $entry */
                $entry = $event->sender;
                $html = '';
                    if ($entry->uri !== null) {
                        $html = $this->renderSidebar($entry);
                    }
                    $event->html .= $html;
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                /** @var Entry $entry */
                $entry = $event->sender;
                self::getInstance()->shortlinks->onAfterSaveEntry($event);
            }
        );

    }

    protected function installGlobalEventListeners(): void
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function() {
                // only use this after all plugins are loaded
                $request = Craft::$app->getRequest();
                // Only non-console site requests
                if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
                    Shortlink::$plugin->shortlinks->handleRedirect();
                }
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
            ],
            'shortlink:entry-redirect' => [
                'label' => Craft::t('shortlink', 'Allow redirect type on entries')
            ]
        ];
    }

    /**
     * @param Entry $entry
     *
     * @return string
     */
    protected function renderSidebar(Entry $entry): string
    {
        $user = Craft::$app->getUser();
        return PluginTemplate::renderPluginTemplate(
          '_sidebars/entry-shortlink.twig',
          [
              'currentSiteId' => $element->siteId ?? 0,
              'showRedirectOption' => $user->checkPermission('shortlink:entry-redirect'),
              'allowCustom' => self::$settings->allowCustom,
              'redirectType' => self::$settings->redirectType,
              'shortlink' => self::getInstance()->shortlinks->getShortlink($entry->id),
          ]
        );
    }

}
