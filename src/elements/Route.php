<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;

use craftpulse\shortlink\elements\conditions\RouteCondition;
use craftpulse\shortlink\elements\db\RouteQuery;
use craftpulse\shortlink\records\RouteRecord;

use yii\base\InvalidConfigException;
use yii\validators\RequiredValidator;
use yii\web\Response;

use DateTime;

/**
 * Route element type
 *
 * @author      CraftPulse
 * @package     Shortlink
 * @since       1.0.0
 */
class Route extends Element
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public int $hitCount = 0;

    /**
     * @var DateTime|null
     */
    public ?DateTime $lastUsed = null;

    /**
     * @var string|null
     */
    public ?string $origin = null;

    /**
     * @var string|null
     */
    public ?string $uriPattern = null;

    /**
     * @var string|null
     */
    public ?string $destination = null;

    /**
     * @var string|null
     */
    public ?string $matchType = null;

    /**
     * @var int|null
     */
    public ?int $httpCode = null;

    /**
     * @var null|FieldLayout Field layout
     */
    private ?FieldLayout $fieldLayout = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('shortlink', 'Route');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('shortlink', 'route');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('shortlink', 'Routes');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('shortlink', 'routes');
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(RouteQuery::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(RouteCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('shortlink', 'All routes'),
                'criteria' => [],
                'defaultSort' => ['dateCreated', 'desc'],
            ],
            [
                'heading' => Craft::t('shortlink', 'Routes'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    /**
     * @inheritdoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'origin' => Craft::t('shortlink', 'Origin'),
            'uriPattern' => Craft::t('shortlink', 'URI pattern'),
            'destination' => Craft::t('shortlink', 'Destination'),
            'matchType' => Craft::t('shortlink', 'Match Type'),
            'httpCode' => Craft::t('shortlink', 'Status'),
            'hitCount' => Craft::t('shortlink', 'Hits'),
            'lastUsed' => Craft::t('shortlink', 'Last Hit'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), [
            'origin' => ['label' => Craft::t('shortlink', 'Origin')],
            'uriPattern' => ['label' => Craft::t('shortlink', 'URI pattern')],
            'destination' => ['label' => Craft::t('shortlink', 'Destination')],
            'matchType' => ['label' => Craft::t('shortlink', 'Match Type')],
            'httpCode' => ['label' => Craft::t('shortlink', 'Status')],
            'hitCount' => ['label' => Craft::t('shortlink', 'Hits')],
            'lastUsed' => ['label' => Craft::t('shortlink', 'Last Hit')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        $attributes[] = 'destination';
        $attributes[] = 'origin';
        $attributes[] = 'matchType';
        $attributes[] = 'httpCode';
        $attributes[] = 'hitCount';
        $attributes[] = 'lastUsed';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['origin', 'uriPattern', 'destination', 'httpCode', 'matchType'], 'safe'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('shortlink:view-routes');
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('shortlink:save-routes');
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('shortlink:save-routes');
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('shortlink:delete-routes');
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('shortlink/routes/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('shortlink/routes');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('shortlink/routes'),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return $this->uriPattern;
        }

        return null;
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function hasRevisions(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     * @return FieldLayout|null
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $this->fieldLayout = Craft::$app->getFields()->getLayoutByType(self::class);

        return $this->fieldLayout;
    }

    /**
     * @inheritdoc
     */
    public function afterValidate(): void
    {
        $scenario = $this->getScenario();

        if ($scenario === self::SCENARIO_LIVE) {
            $routeElements = $this->getFieldLayout()->getAllElements();
            foreach ($routeElements as $routeElement) {
                if ($routeElement->required) {
                    (new RequiredValidator())->validateAttribute($this, $routeElement->attribute);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Reset stats if it's a duplicate of a non-draft entry
        if ($isNew && $this->getIsCanonical() && $this->duplicateOf !== null) {
            $this->lastUsed = null;
            $this->hitCount = 0;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $routeRecord = new RouteRecord();
                $routeRecord->id = $this->id;
            } else {
                $routeRecord = RouteRecord::findOne($this->id);
            }

            $routeRecord->lastUsed = $this->lastUsed;
            $routeRecord->hitCount = $this->hitCount;
            $routeRecord->origin = $this->origin;
            $routeRecord->destination = $this->destination;
            $routeRecord->matchType = $this->matchType;
            $routeRecord->httpCode = (int)$this->httpCode;
            $routeRecord->uriPattern = $this->uriPattern;
            $routeRecord->fieldLayoutId = $this->fieldLayout->id;

            $routeRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
