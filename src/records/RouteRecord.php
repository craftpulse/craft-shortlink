<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\records;

use craft\base\Element;
use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use DateTime;

/**
 * Class SettingsModel
 *
 * @author      CraftPulse
 * @package     Shortlink
 *
 * @property int $id
 * @property int $hitCount
 * @property DateTime|null $lastUsed
 * @property string $origin
 * @property string $uriPattern
 * @property string $destination
 * @property string $matchType
 * @property int $httpCode
 * @property int|null $fieldLayoutId
 *
 * @property-read Element $element
 */
class RouteRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%shortlink_routes}}';
    }

    /**
     * Returns the related element
     */
    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
