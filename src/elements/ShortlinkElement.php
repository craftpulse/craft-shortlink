<?php

namespace percipiolondon\shortlink\elements;

use Craft;
use craft\base\Element;
use craft\elements\Entry;

use DateTime;

use percipiolondon\shortlink\elements\db\ShortlinkQuery;

class ShortlinkElement extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // Properties
    // =========================================================================

    public ?int $ownerId = null;
    public ?string $shortlinkUri = null;
    public ?string $destination = null;
    public ?string $httpCode = null;
    public ?string $hitCount = null;
    public ?DateTime $lastUsed = null;

    // Static Methods
    // =========================================================================
    public static function displayName(): string
    {
        return Craft::t('shortlink', 'Shortlink');
    }

    public static function refHandle(): ?string
    {
        return 'shortlink';
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
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => Craft::t('shortlink', 'Active'),
            self::STATUS_INACTIVE => Craft::t('shortlink', 'Inactive'),
        ];
    }

    public static function find(): ShortlinkQuery
    {
        return new ShortlinkQuery(static::class);
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('workflow', 'Entry')],
            'siteId' => ['label' => Craft::t('workflow', 'Site')],
            'dateCreated' => ['label' => Craft::t('workflow', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('workflow', 'Date Updated')],
            'shortlinkUri' => ['label' => Craft::t('workflow', 'URI')],
            'httpCode' => ['label' => Craft::t('workflow', 'HTTP Redirect Code')],
            'hitCount' => ['label' => Craft::t('workflow', 'Hit Count')],
            'lastUsed' => ['label' => Craft::t('workflow', 'Last Used')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'dateCreated',
            'shortlinkUri',
            'httpCode',
            'hitCount'
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('workflow', 'Entry'),
            'dateCreated' => Craft::t('workflow', 'Date Created'),
            'shortlinkUri' => Craft::t('workflow', 'URI'),
            'httpCode' => Craft::t('workflow', 'HTTP Redirect Code'),
        ];
    }

    // Public Methods
    // =========================================================================

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
