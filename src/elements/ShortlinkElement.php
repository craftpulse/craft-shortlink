<?php

namespace percipiolondon\shortlink\elements;

use Craft;
use craft\base\Element;

use DateTime;

use Exception;
use percipiolondon\shortlink\elements\db\ShortlinkQuery;
use percipiolondon\shortlink\records\ShortlinkRecord;

class ShortlinkElement extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // Properties
    // =========================================================================

    public ?int $ownerId = null;
    public ?int $ownerRevisionId = null;
    public ?string $shortlinkUri = null;
    public ?string $destination = null;
    public ?string $httpCode = null;
    public ?string $hitCount = null;
    public ?string $lastUsed = null;
    public string $shortlinkStatus = self::STATUS_ACTIVE;

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
            'id' => ['label' => Craft::t('shortlink', 'Entry')],
            'siteId' => ['label' => Craft::t('shortlink', 'Site')],
            'dateCreated' => ['label' => Craft::t('shortlink', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('shortlink', 'Date Updated')],
            'shortlinkUri' => ['label' => Craft::t('shortlink', 'URI')],
            'httpCode' => ['label' => Craft::t('shortlink', 'HTTP Redirect Code')],
            'hitCount' => ['label' => Craft::t('shortlink', 'Hit Count')],
            'lastUsed' => ['label' => Craft::t('shortlink', 'Last Used')],
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
            'id' => Craft::t('shortlink', 'Entry'),
            'dateCreated' => Craft::t('shortlink', 'Date Created'),
            'shortlinkUri' => Craft::t('shortlink', 'URI'),
            'httpCode' => Craft::t('shortlink', 'HTTP Redirect Code'),
        ];
    }

    // Public Methods
    // =========================================================================

    public function getStatus(): ?string
    {
        return $this->shortlinkStatus;
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = ShortlinkRecord::findOne($this->id);

            if(!$record) {
                throw new Exception('Invalid shortlink ID: ' . $this->id);
            }
        } else {
            $record = new ShortlinkRecord();
            $record->id = $this->id;
        }

        $record->siteId = Craft::$app->getSites()->currentSite->id;
        $record->ownerId = $this->ownerId;
        $record->ownerRevisionId = $this->ownerRevisionId;
        $record->shortlinkUri = $this->shortlinkUri;
        $record->destination = $this->destination ?? null;
        $record->httpCode = $this->httpCode;
        $record->hitCount = $this->hitCount;
        $record->lastUsed = $this->lastUsed;
        $record->shortlinkStatus = $this->shortlinkStatus;

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }
}
