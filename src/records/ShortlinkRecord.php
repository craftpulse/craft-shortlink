<?php

namespace percipiolondon\shortlink\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;
use percipiolondon\shortlink\db\Table;

/**
 * Class ShortlinkRecord
 *
 * @package percipiolondon\shortlink\records
 * @property integer $id
 * @property integer $siteId
 * @property integer $ownerId
 * @property string $shortlinkUri
 * @property string $destination
 * @property string $httpCode
 * @property integer $hitCount
 * @property \DateTime $lastUsed
 * @property string $status
 *
 */

class ShortlinkRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ROUTES;
    }

    public function getShortlink(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getOwner(): ActiveQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'ownerId']);
    }
}
