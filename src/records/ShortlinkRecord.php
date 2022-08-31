<?php

namespace percipiolondon\shortlink\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Entry;

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
