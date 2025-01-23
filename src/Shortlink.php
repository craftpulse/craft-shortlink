<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink;

use Craft;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use Throwable;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fieldlayoutelements\TextField;
use craft\log\MonologTarget;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\UrlManager;

use craftpulse\shortlink\elements\Route;
use craftpulse\shortlink\models\SettingsModel;
use craftpulse\shortlink\services\ServicesTrait;

use yii\base\Event;
use yii\base\InvalidRouteException;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * Class Shortlink
 *
 * @author      CraftPulse
 * @package     Shortlink
 * @since       1.0.0
 *
 * @method Settings getSettings()
 */
class Shortlink extends Plugin
{
    // Traits
    // =========================================================================

    use ServicesTrait;

    // Static Properties
    // =========================================================================
    /**
     * @var ?Shortlink
     */
    public static ?Shortlink $plugin = null;

    // Public Properties
    // =========================================================================

    /**
     * @var null|SettingsModel
     */
    public static ?SettingsModel $settings = null;
    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';
    /**
     * @var bool
     */
    public bool $hasCpSection = true;
    /**
     * @var bool
     */
    public bool $hasCpSettings = true;
    /**
     * @var mixed|object|null
     */
    public mixed $queue = null;

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Register custom log target
        $this->registerLogTarget();

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'craftpulse\shortlink\console\controllers';
        }

        // Install our global event handlers
        $this->installEventHandlers();

        // Register control panel events
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->registerCpUrlRules();
            $this->registerRouteFieldLayout();
        }

        // Register site events
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->registerSiteEventHandlers();
        }

        // Log that the plugin has loaded
        Craft::info(
            Craft::t(
                'shortlink',
                '{name} plugin loaded',
                ['name' => $this->name]
            )
        );
    }

    /**
     * Logs a message
     * @throws Throwable
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        $encoded_params = str_replace('\\', '', Json::encode($params));

        $message = Craft::t('shortlink', $message . ' ' . $encoded_params, $params);

        Craft::getLogger()->log($message, $type, 'shortlink');
    }

    /**
     * @inheritdoc
     * @throws InvalidRouteException
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect('shortlink/settings');
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $editableSettings = true;
        $general = Craft::$app->getConfig()->getGeneral();

        if (!$general->allowAdminChanges) {
            $editableSettings = false;
        }

        if ($currentUser->can('shortlink:view-routes')) {
            $subNavs['routes'] = [
                'label' => 'Routes',
                'url' => 'shortlink/routes',
            ];
        }

        if ($currentUser->can('shortlink:settings') && $editableSettings) {
            $subNavs['settings'] = [
                'label' => 'Settings',
                'url' => 'shortlink/settings',
            ];
        }

        if (empty($subNavs)) {
            return null;
        }

        // A single sub nav item is redundant
        if (count($subNavs) === 1) {
            $subNavs = [];
        }

        return array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'shortlink/settings/_edit',
            ['settings' => $this->getSettings()]
        );
    }



    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new SettingsModel();
    }

    /**
     * @return void
     */
    protected function installEventHandlers(): void
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                    Craft::debug(
                        'Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS',
                        __METHOD__
                    );
                }
            }
        );

        // Register Element Types
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Route::class;
            }
        );

        $this->registerUserPermissions();
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers CP URL rules event
     */
    private function registerCpUrlRules(): void
    {
        Event::on(UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Merge so that settings controller action comes first (important!)
                $event->rules = array_merge(
                    [
                        'shortlink' => 'shortlink/settings/edit',
                        'shortlink/settings' => 'shortlink/settings/edit',
                        'shortlink/plugins/shortlink' => 'shortlink/settings/edit',
                        'shortlink/routes' => ['template' => 'shortlink/routes/_index.twig'],
                        'shortlink/routes/<elementId:\d+>' => 'elements/edit',
                    ],
                    $event->rules,
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    private function registerSiteEventHandlers(): void
    {
        Event::on(
            Application::class,
            Application::EVENT_BEFORE_REQUEST,
            function (Event $event) {
                $request = Craft::$app->getRequest();
                // only handle this on actual site requests
                if ($request->getIsSiteRequest() && !$request->getIsLivePreview() && !$request->getIsConsoleRequest() && !$request->getIsCpRequest()) {
                    Shortlink::$plugin->redirects->handleRedirect();
                }
            }
        );
    }

    /**
     * Registers user permissions
     */
    private function registerUserPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Shortlink',
                    'permissions' => [
                        'shortlink:settings' => [
                            'label' => Craft::t('shortlink', 'Manage plugin settings.'),
                        ],
                        'shortlink:view-routes' => [
                            'label' => Craft::t('shortlink', 'View shortlink routes.'),
                        ],
                        'shortlink:save-routes' => [
                            'label' => Craft::t('shortlink', 'Save/edit shortlink routes.'),
                        ],
                        'shortlink:delete-routes' => [
                            'label' => Craft::t('shortlink', 'Delete shortlink routes.'),
                        ],
                    ],
                ];
            }
        );
    }

    private function registerRouteFieldLayout(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function (DefineFieldLayoutFieldsEvent $event) {
                /** @var FieldLayout $fieldLayout */
                $fieldLayout = $event->sender;

                // We only want to provide these options for our route field layouts:
                if ($fieldLayout->type !== Route::class) {
                    return;
                }

                // Add our custom fields
                foreach ($this->getRoutes()->createFields() as $field)
                {
                    $event->fields[] = $field;
                }
            }
        );
    }

    /**
     * Registers a custom log target
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function registerLogTarget(): void
    {
        if (Craft::getLogger()->dispatcher instanceof Dispatcher) {
            Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
                'name' => 'shortlink',
                'categories' => ['shortlink'],
                'level' => LogLevel::INFO,
                'logContext' => false,
                'allowLineBreaks' => true,
                'formatter' => new LineFormatter(
                    format: "%datetime% [%channel%.%level_name%] %message% %context%\n",
                    dateFormat: 'Y-m-d H:i:s',
                ),
            ]);
        }
    }
}
