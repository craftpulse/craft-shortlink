<?php

namespace percipiolondon\shortlink;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\DefineHtmlEvent;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RevisionEvent;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\Revisions;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use nystudio107\pluginvite\services\VitePluginService;
use percipiolondon\shortlink\assetbundles\shortlink\ShortlinkAsset;
use percipiolondon\shortlink\elements\ShortlinkElement;
use percipiolondon\shortlink\helpers\PluginTemplate;
use percipiolondon\shortlink\models\SettingsModel as Settings;
use percipiolondon\shortlink\services\ShortlinkService;
use percipiolondon\shortlink\variables\ShortlinkVariable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\event;
use yii\base\ModelEvent as ModelEventYii;

/**
 *
 * @author    percipiolondon
 * @package   Shortlink
 * @since     1.0.0
 *
 * @property ShortlinkService $shortlinkService
 * @property VitePluginService $vite
 * @property Settings $settings
 * @property-read mixed $settingsResponse
 * @property-read null|array $cpNavItem
 * @property mixed|object|null $shortlinks
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
            'shortlinks' => ShortlinkService::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => ShortlinkAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3751',
                'serverPublic' => 'http://localhost:3700',
                'errorEntry' => 'src/js/shortlink.ts',
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

        // Register elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event): void {
                $event->types[] = ShortlinkElement::class;
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
        if ($currentUser->can('shortlink:static-shortlinks')) {
            $subNavs['static-shortlinks'] = [
                'label' => Craft::t('shortlink', 'Static Shortlinks'),
                'url' => 'shortlink/static-shortlinks',
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
                    'heading' => Craft::t('shortlink', 'Shortlink'),
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
            Entry::EVENT_BEFORE_VALIDATE,
            function (ModelEventYii $event) {
                /** @var Entry $entry */
                $entry = $event->sender;

                if(!ElementHelper::isDraftOrRevision($entry)) {
                    $shortlinkUri = Craft::$app->getRequest()->getBodyParam('shortlink-uri') ?? '';

                    if ($shortlinkUri) {
                        // check for duplication
                        $shortlink = ShortlinkElement::find()
                            ->where(['not', ['ownerId' => null]])
                            ->andWhere(['not', ['ownerId' => $entry->id]])
                            ->andWhere(['shortlinkUri' => $shortlinkUri])->one();

                        if (isset($shortlink)) {
                            $entry->addError('shortlinkUri', Craft::t('shortlink','The shortlink already exists'));
                        }

                        // check if shortlink is valid
                        if (!preg_match('/^[a-z0-9]+(-?[a-z0-9]+)*$/i', $shortlinkUri)) {
                            $entry->addError('shortlinkUri', Craft::t('shortlink','The shortlink is not valid'));
                        }
                    }
                }
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                /** @var Entry $entry */
                $entry = $event->sender;

//                if ($entry->updatingFromDerivative) {
//                    return;
//                }

                if (($event->sender->duplicateOf && $event->sender->getIsCanonical() && !$event->sender->updatingFromDerivative)) {
                    self::getInstance()->shortlinks->onAfterDuplicateEntry($entry);
                } else {
                    self::getInstance()->shortlinks->onAfterSaveEntry($entry);
                }

//                elseif
//                    ($entry->updatingFromDerivative){
//                    Craft::info('Shortlink: updatingFromDerivative');
//                }
            }
        );

//        Event::on(
//            Revisions::class,
//            Revisions::EVENT_BEFORE_REVERT_TO_REVISION,
//            function (RevisionEvent $event) {
//
//                $shortlink = ShortlinkElement::findOne(['ownerRevisionId' => $event->revision->id]);
//                $prevShortlink = ShortlinkElement::findOne(['ownerId' => $event->canonical->id]);
//
//                if (!is_null($prevShortlink)) {
//                    $prevShortlink->ownerRevisionId = $event->revision->id;
//                    $prevShortlink->ownerId = null;
//                    Craft::$app->getElements()->saveElement($prevShortlink);
//                }
//
//                if (!is_null($shortlink)) {
//                    $shortlink->ownerRevisionId = null;
//                    $shortlink->ownerId = $event->canonical->id;
//                    Craft::$app->getElements()->saveElement($shortlink);
//                }
//            }
//        );
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
            'shortlink/static-shortlinks' => 'shortlink/settings/static-shortlinks',
            'shortlink/static-shortlinks/add' => 'shortlink/settings/static-shortlinks-add',
            'shortlink/static-shortlinks/edit/<shortlinkId:\d+>' => 'shortlink/settings/static-shortlinks-edit',
            'shortlink/static-shortlinks/delete/<shortlinkId:\d+>' => 'shortlink/settings/static-shortlinks-delete',
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
            'shortlink:static-shortlinks' => [
                'label' => Craft::t('shortlink', 'Static Shortlinks'),
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
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderSidebar(Entry $entry): string
    {
        $user = Craft::$app->getUser();
        $shortlink = $this->_getShortlinkFromContext($entry);

        $request = Craft::$app->getRequest();

        //set vars from request if exists, otherwise fetch them
        $shortlinkVars = [
            'allowCustom' => $request->getBodyParam('shortlink-allow-custom') ?? self::$settings->allowCustom,
            'currentSiteId' => $element->siteId ?? 0,
            'redirectType' => $request->getBodyParam('shortlink-redirect-type') ?? self::$settings->redirectType,
            'shortlink' => $request->getBodyParam('shortlink-uri') ?? $shortlink->shortlinkUri ?? self::getInstance()->shortlinks->generateShortlink(),
            'shortlinkId' => $request->getBodyParam('shortlinkId') ?? $shortlink->id ?? 0,
            'showRedirectOption' => $request->getBodyParam('shortlink-show-redirect-option') ?? $user->checkPermission('shortlink:entry-redirect'),
            'shortlinkUrls' => self::$settings->shortlinkUrls,
            'shortlinkErrors' => $entry->hasErrors() ? ($entry->getErrors()['shortlinkUri'] ?? []) : null
        ];

        return PluginTemplate::renderPluginTemplate(
            '_sidebars/entry-shortlink.twig',
            $shortlinkVars
        );
    }

    private function _getShortlinkFromContext($entry): ?ShortlinkElement
    {
        // Get existing shortlink
        $ownerId = $entry->id ?? ':empty:';

        if(!ElementHelper::isDraftOrRevision($entry)) {
            return ShortlinkElement::find()
                ->ownerId($ownerId)
                ->one();
        }

        return ShortlinkElement::find()
            ->ownerRevisionId($ownerId)
            ->one();

    }

}
